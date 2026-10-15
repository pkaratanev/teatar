<?php

namespace App\Filament\Resources\Productions;

use App\Enums\CreditRole;
use App\Enums\ProductionStatus;
use App\Filament\Resources\Productions\Pages\CreateProduction;
use App\Filament\Resources\Productions\Pages\EditProduction;
use App\Filament\Resources\Productions\Pages\ListProductions;
use App\Filament\Resources\Productions\RelationManagers\PerformancesRelationManager;
use App\Models\Production;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'постановка';

    protected static ?string $pluralModelLabel = 'постановки';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Постановка')->columns(2)->schema([
                TextInput::make('title')->label('Заглавие')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Set $set, ?Production $record) => $record ? null : $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('theatre_id')->label('Театър')->relationship('theatre', 'name')->searchable()->preload()->required(),
                Select::make('status')->label('Статус')->options(ProductionStatus::class)->default(ProductionStatus::Running)->required(),
                TextInput::make('author')->label('Автор'),
                TextInput::make('age_advisory')->label('Възраст')->placeholder('12+')->maxLength(8),
                DatePicker::make('premiered_on')->label('Премиера'),
                TextInput::make('running_time_minutes')->label('Продължителност (мин.)')->numeric(),
                Textarea::make('synopsis')->label('Анотация')->rows(4)
                    ->helperText('Свой текст или текст, предоставен от театъра. Не копирайте от theatre.art.bg.')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('poster')->label('Плакат')->collection('poster')->image(),
                TextInput::make('title_en')->label('Заглавие (EN)'),
            ]),
            Section::make('Екип и актьори')->schema([
                Repeater::make('credits')->hiddenLabel()->relationship()
                    ->orderColumn('sort')
                    ->columns(3)
                    ->schema([
                        Select::make('person_id')->label('Човек')->relationship('person', 'name')->searchable()->required()
                            ->createOptionForm([
                                TextInput::make('name')->label('Име')->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug((string) $state).'-'.Str::lower(Str::random(4)))),
                                TextInput::make('slug')->required(),
                            ]),
                        Select::make('role_type')->label('Роля в екипа')->options(CreditRole::class)->default(CreditRole::Actor)->required(),
                        TextInput::make('character_name')->label('Персонаж'),
                    ])
                    ->addActionLabel('Добави човек'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('poster')->collection('poster')->label(''),
                TextColumn::make('title')->label('Заглавие')->searchable()->sortable(),
                TextColumn::make('theatre.name')->label('Театър')->sortable(),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('credits_count')->label('Хора')->counts('credits'),
                TextColumn::make('rating_average')->label('★')->numeric(2)->sortable(),
                TextColumn::make('source')->label('Източник')->badge()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('title')
            ->filters([
                SelectFilter::make('theatre')->label('Театър')->relationship('theatre', 'name'),
                SelectFilter::make('status')->label('Статус')->options(ProductionStatus::class),
                TernaryFilter::make('incomplete')
                    ->label('Липсват данни')
                    ->placeholder('Всички')
                    ->trueLabel('Без екип или автор')
                    ->falseLabel('С екип и автор')
                    ->queries(
                        true: fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('author')->orWhereDoesntHave('credits')),
                        false: fn (Builder $q) => $q->whereNotNull('author')->whereHas('credits'),
                    ),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [PerformancesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductions::route('/'),
            'create' => CreateProduction::route('/create'),
            'edit' => EditProduction::route('/{record}/edit'),
        ];
    }
}
