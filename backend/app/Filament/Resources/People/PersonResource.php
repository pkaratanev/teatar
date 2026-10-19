<?php

namespace App\Filament\Resources\People;

use App\Filament\Resources\People\Pages\ManagePeople;
use App\Models\Person;
use App\Services\PeopleDuplicates;
use App\Services\PersonMerge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'човек';

    protected static ?string $pluralModelLabel = 'хора';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Име')->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Set $set, ?Person $record) => $record ? null : $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('born_year')->label('Година на раждане')->numeric()->minValue(1900)->maxValue(now()->year),
            SpatieMediaLibraryFileUpload::make('headshot')->label('Снимка')->collection('headshot')->image()->avatar(),
            Textarea::make('bio')->label('Биография')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('headshot')->collection('headshot')->circular()->label(''),
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('name')->label('Име')->searchable()->sortable(),
                TextColumn::make('credits_count')->label('Участия')->counts('credits')->sortable(),
                // Which productions, so namesakes (two „Стоян Радев“) can be told apart before merging.
                TextColumn::make('productions_list')->label('Постановки')
                    ->state(fn (Person $record) => $record->credits
                        ->map(fn ($credit) => $credit->production->title.' ('.mb_strtolower($credit->role_type->getLabel()).', '.$credit->production->theatre->short_name.')')
                        ->unique()->values()->all())
                    ->listWithLineBreaks()->limitList(3)->expandableLimitedList()->wrap(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('credits.production.theatre'))
            ->defaultSort('name')
            ->filters([
                // Groups people who are probably the same person next to each other (see PeopleDuplicates).
                Filter::make('duplicates')->label('Възможни дубликати')->toggle()
                    ->query(function (Builder $query) {
                        $ids = app(PeopleDuplicates::class)->ids();
                        if ($ids === []) {
                            return $query->whereRaw('1 = 0');
                        }
                        $order = collect($ids)->map(fn (int $id, int $i) => "WHEN {$id} THEN {$i}")->implode(' ');

                        return $query->whereIn('id', $ids)->orderByRaw("CASE id {$order} END");
                    }),
            ])
            ->recordActions([EditAction::make(), self::mergeAction(), DeleteAction::make()])
            ->toolbarActions([self::mergeSelectedAction()]);
    }

    /** Tick two or more people, pick the one that stays; the others are merged into it. */
    public static function mergeSelectedAction(): BulkAction
    {
        return BulkAction::make('mergeSelected')
            ->label('Слей избраните')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('gray')
            ->modalHeading('Слей избраните хора в един')
            ->modalDescription('Участията и последователите на всички избрани се преместват при този, който остава; другите записи се изтриват. Не може да се върне.')
            ->modalSubmitActionLabel('Слей')
            ->schema(fn (Collection $records) => [
                Select::make('keep_id')
                    ->label('Остава')
                    ->required()
                    ->options(self::pickOptions($records))
                    ->default(array_key_first(self::pickOptions($records))),
            ])
            ->action(function (Collection $records, array $data, PersonMerge $merge): void {
                $keep = $records->firstWhere('id', (int) $data['keep_id']);
                if (! $keep instanceof Person || $records->count() < 2) {
                    Notification::make()->title('Изберете поне двама души и кой остава')->danger()->send();

                    return;
                }

                $moved = ['credits' => 0, 'followers' => 0];
                $names = [];
                foreach ($records as $person) {
                    if ($person instanceof Person && ! $person->is($keep)) {
                        $names[] = $person->name;
                        $result = $merge->merge($keep, $person);
                        $moved['credits'] += $result['credits'];
                        $moved['followers'] += $result['followers'];
                    }
                }

                Notification::make()
                    ->title('Слети в „'.$keep->name.'“: '.count($names))
                    ->body(implode(', ', $names)." · преместени участия: {$moved['credits']}, последователи: {$moved['followers']}")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /** "Слей в…": this row is the duplicate; its credits and followers move to the person picked here. */
    public static function mergeAction(): Action
    {
        return Action::make('merge')
            ->label('Слей в…')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('gray')
            ->modalHeading(fn (Person $record) => "Слей „{$record->name}“ (#{$record->id}) в друг човек")
            ->modalDescription('Участията и последователите се преместват при избрания човек, а този запис се изтрива. Не може да се върне.')
            ->modalSubmitActionLabel('Слей')
            ->schema([
                Select::make('keep_id')
                    ->label('Остава')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, Person $record) => Person::query()
                        ->whereKeyNot($record->getKey())
                        ->where('name', 'like', '%'.trim($search).'%')
                        ->withCount('credits')
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Person $p) => [$p->id => self::pickLabel($p)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value) => ($p = Person::withCount('credits')->find($value)) ? self::pickLabel($p) : null),
            ])
            ->action(function (Person $record, array $data, PersonMerge $merge): void {
                $keep = Person::find($data['keep_id']);
                if ($keep === null || $keep->is($record)) {
                    Notification::make()->title('Изберете друг човек')->danger()->send();

                    return;
                }

                $name = $record->name;
                $moved = $merge->merge($keep, $record);
                Notification::make()
                    ->title("„{$name}“ е слят в „{$keep->name}“")
                    ->body("Преместени участия: {$moved['credits']}, последователи: {$moved['followers']}.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Selected people as options, the one with the most credits first.
     *
     * @param  Collection<int, \Illuminate\Database\Eloquent\Model>  $records
     * @return array<int, string>
     */
    private static function pickOptions(Collection $records): array
    {
        return Person::query()->whereKey($records->modelKeys())->withCount('credits')
            ->orderByDesc('credits_count')->orderBy('id')->get()
            ->mapWithKeys(fn (Person $p) => [$p->id => self::pickLabel($p)])->all();
    }

    private static function pickLabel(Person $person): string
    {
        $titles = $person->productions()->limit(2)->pluck('title')->implode(', ');

        return "{$person->name} (#{$person->id}, участия: {$person->credits_count}".($titles !== '' ? " — {$titles}" : '').')';
    }

    public static function getPages(): array
    {
        return ['index' => ManagePeople::route('/')];
    }
}
