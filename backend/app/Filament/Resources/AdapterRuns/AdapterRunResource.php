<?php

namespace App\Filament\Resources\AdapterRuns;

use App\Filament\Resources\AdapterRuns\Pages\ListAdapterRuns;
use App\Filament\Resources\AdapterRuns\Pages\TheatreAdapterRuns;
use App\Models\AdapterRun;
use App\Services\SyncAlerts;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Read-only log of programme syncs: which theatre broke, when, and why. Latest per theatre, then each theatre's history. */
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
                TextColumn::make('theatre.name')->label('Театър')->sortable(),
                TextColumn::make('status')->label('Статус')->badge()
                    ->formatStateUsing(fn (string $state) => AdapterRun::LABELS[$state] ?? $state)
                    ->color(fn (string $state) => AdapterRun::color($state)),
                TextColumn::make('performances_found')->label('Намерени'),
                TextColumn::make('performances_upserted')->label('Нови / променени'),
                TextColumn::make('performances_moved')->label('Преместени'),
                TextColumn::make('performances_cancelled')->label('Отменени'),
                TextColumn::make('productions_created')->label('Нови постановки'),
                TextColumn::make('credits_added')->label('Екип (нови)')->toggleable(),
                // Hover shows the whole message; the column keeps the start.
                TextColumn::make('error')->label('Грешка')->limit(60)->wrap()->tooltip(fn (?string $state) => $state),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['succeeded' => 'Успешни', 'failed' => 'Неуспешни', 'running' => 'Текат', 'queued' => 'На опашка']),
                SelectFilter::make('theatre')->relationship('theatre', 'name'),
            ]);
    }

    /** Red count of theatres whose sync needs a look (failed, found nothing, or stale). */
    public static function getNavigationBadge(): ?string
    {
        $count = SyncAlerts::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Театри с проблем в синхронизацията';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdapterRuns::route('/'),
            'theatre' => TheatreAdapterRuns::route('/theatre/{theatre}'),
        ];
    }
}
