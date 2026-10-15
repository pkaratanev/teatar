<?php

namespace App\Filament\Resources\Theatres;

use App\Filament\Resources\Theatres\Pages\ManageTheatres;
use App\Jobs\SyncTheatreProgramme;
use App\Models\Theatre;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class TheatreResource extends Resource
{
    protected static ?string $model = Theatre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'театър';

    protected static ?string $pluralModelLabel = 'театри';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Театър')->columns(2)->schema([
                TextInput::make('name')->label('Име')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Set $set, ?Theatre $record) => $record ? null : $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('city')->label('Град')->default('София')->required(),
                TextInput::make('address')->label('Адрес'),
                TextInput::make('lat')->numeric(),
                TextInput::make('lng')->numeric(),
                TextInput::make('website')->label('Сайт')->url(),
                TextInput::make('box_office_url')->label('Каса / билети')->url(),
                TextInput::make('phone')->label('Телефон'),
            ]),
            Section::make('Програма')->columns(2)->schema([
                Select::make('adapter')
                    ->label('Източник на програмата')
                    ->options(fn () => array_combine(array_keys(config('teatar.adapters')), array_keys(config('teatar.adapters'))))
                    ->placeholder('Ръчно въвеждане'),
                Toggle::make('is_active')->label('Активен')->default(true),
                Repeater::make('stages')->label('Сцени')->relationship()
                    ->simple(TextInput::make('name')->required())
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Име')->searchable()->sortable(),
                TextColumn::make('city')->label('Град')->sortable(),
                TextColumn::make('productions_count')->label('Постановки')->counts('productions')->sortable(),
                TextColumn::make('adapter')->label('Програма')->badge(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('sync')
                    ->label('Обнови програмата')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Theatre $record) => filled($record->adapter))
                    ->action(function (Theatre $record) {
                        SyncTheatreProgramme::dispatch($record);
                        Notification::make()->title('Синхронизацията е пусната')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTheatres::route('/'),
        ];
    }
}
