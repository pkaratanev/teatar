<?php

namespace App\Filament\Resources\AdapterRuns;

use App\Filament\Resources\AdapterRuns\Pages\ListAdapterRuns;
use App\Models\AdapterRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Read-only log of programme syncs: which theatre broke, when, and why. */
class AdapterRunResource extends Resource
{
    protected static ?string $model = AdapterRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Данни';

    protected static ?string $modelLabel = 'синхронизация';

    protected static ?string $pluralModelLabel = 'синхронизации';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('started_at')->label('Начало')->dateTime('d.m.Y H:i', config('app.theatre_timezone'))->sortable(),
                TextColumn::make('theatre.name')->label('Театър'),
                TextColumn::make('status')->label('Статус')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('performances_found')->label('Намерени'),
                TextColumn::make('performances_upserted')->label('Нови / променени'),
                TextColumn::make('productions_created')->label('Нови постановки'),
                TextColumn::make('error')->label('Грешка')->limit(60)->wrap(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['succeeded' => 'Успешни', 'failed' => 'Неуспешни']),
                SelectFilter::make('theatre')->relationship('theatre', 'name'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListAdapterRuns::route('/')];
    }
}
