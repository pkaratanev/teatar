<?php

namespace App\Filament\Resources\Productions\RelationManagers;

use App\Models\Performance;
use App\Models\Production;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PerformancesRelationManager extends RelationManager
{
    protected static string $relationship = 'performances';

    protected static ?string $title = 'Представления';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('starts_at')->label('Начало')->seconds(false)
                ->timezone(config('app.theatre_timezone'))->required(),
            Select::make('stage_id')->label('Сцена')
                ->relationship('stage', 'name', fn (Builder $q) => $q->where('theatre_id', $this->production()->theatre_id)),
            TextInput::make('ticket_url')->label('Билети')->url(),
            TextInput::make('price_from')->label('Цена от (€)')->numeric(),
            TextInput::make('price_to')->label('Цена до (€)')->numeric(),
            Toggle::make('is_cancelled')->label('Отменено'),
        ]);
    }

    private function production(): Production
    {
        /** @var Production */
        return $this->getOwnerRecord();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Начало')->dateTime('d.m.Y H:i', config('app.theatre_timezone'))->sortable(),
                TextColumn::make('stage.name')->label('Сцена'),
                TextColumn::make('price_from')->label('От')->money('EUR'),
                IconColumn::make('is_cancelled')->label('Отменено')->boolean(),
                TextColumn::make('source')->label('Източник')->badge(),
            ])
            ->defaultSort('starts_at')
            ->modifyQueryUsing(fn (Builder $q) => $q->where('starts_at', '>=', now()->subDays(30)))
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Performance $record) => $record->source !== 'adapter'),
            ]);
    }
}
