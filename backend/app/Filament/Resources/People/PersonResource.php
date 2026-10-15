<?php

namespace App\Filament\Resources\People;

use App\Filament\Resources\People\Pages\ManagePeople;
use App\Models\Person;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                TextColumn::make('name')->label('Име')->searchable()->sortable(),
                TextColumn::make('credits_count')->label('Участия')->counts('credits')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePeople::route('/')];
    }
}
