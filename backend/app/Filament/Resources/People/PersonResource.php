<?php

namespace App\Filament\Resources\People;

use App\Filament\Resources\People\Pages\ManagePeople;
use App\Models\Person;
use BackedEnum;
use App\Services\PersonMerge;
use Filament\Actions\Action;
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
use Filament\Tables\Table;
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
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), self::mergeAction()]);
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

    private static function pickLabel(Person $person): string
    {
        return "{$person->name} (#{$person->id}, участия: {$person->credits_count})";
    }

    public static function getPages(): array
    {
        return ['index' => ManagePeople::route('/')];
    }
}
