<?php

namespace App\Filament\Widgets;

use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use App\Services\ProgrammeSync;
use App\Support\SyncSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** One row per theatre with an adapter: did last night's sync work, and how far ahead do we know the programme? */
class SyncHealth extends TableWidget
{
    // Render with the page: three small queries, and the dashboard is the admin's first screen.
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $lastRun = fn (string $column) => AdapterRun::select($column)
            ->whereColumn('adapter_runs.theatre_id', 'theatres.id')
            ->latest('started_at')
            ->limit(1);

        return $table
            ->heading('Синхронизация по театри')
            ->description(fn () => SyncSchedule::summary())
            ->query(
                Theatre::query()
                    ->whereNotNull('adapter')
                    ->addSelect([
                        'last_status' => $lastRun('status'),
                        'last_started_at' => $lastRun('started_at'),
                        'last_found' => $lastRun('performances_found'),
                        'last_error' => $lastRun('error'),
                        'last_performance_at' => Performance::selectRaw('max(starts_at)')
                            ->join('productions', 'productions.id', '=', 'performances.production_id')
                            ->whereColumn('productions.theatre_id', 'theatres.id'),
                    ])
                    ->withCount(['performances as upcoming_count' => fn ($q) => $q->where('performances.starts_at', '>=', now())])
            )
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Театър'),
                TextColumn::make('adapter')->label('Източник')->badge()->color('gray'),
                TextColumn::make('last_status')->label('Последно')->badge()
                    ->formatStateUsing(fn (?string $state) => AdapterRun::LABELS[$state] ?? 'Не е пускана')
                    ->color(fn (?string $state) => AdapterRun::color($state))
                    ->tooltip(fn (Theatre $record) => $record->getAttribute('last_error')),
                TextColumn::make('last_started_at')->label('Кога')->since(),
                TextColumn::make('last_found')->label('Намерени'),
                TextColumn::make('upcoming_count')->label('Предстоящи'),
                TextColumn::make('last_performance_at')->label('Програма до')
                    ->date('d.m.Y', config('app.theatre_timezone')),
            ])
            ->headerActions([
                self::syncAllAction(),
            ])
            ->recordActions([
                Action::make('sync')->label('Обнови')->icon(Heroicon::OutlinedArrowPath)
                    ->action(fn (Theatre $record) => self::syncOne($record)),
            ]);
    }

    public static function syncOne(Theatre $theatre): void
    {
        app(ProgrammeSync::class)->queue($theatre)
            ? Notification::make()->title("Синхронизацията на {$theatre->name} е пусната")->success()->send()
            : Notification::make()->title("{$theatre->name} вече се синхронизира")->warning()->send();
    }

    /** "Обнови всички": on the dashboard and the sync log. */
    public static function syncAllAction(): Action
    {
        return Action::make('syncAll')
            ->label('Обнови всички')
            ->icon(Heroicon::OutlinedArrowPath)
            ->requiresConfirmation()
            ->modalDescription('Пуска синхронизация за всеки активен театър с източник. Театри, които вече се синхронизират, се пропускат.')
            ->action(function () {
                $result = app(ProgrammeSync::class)->queueAll();
                $note = Notification::make()->title('Пуснати: '.count($result['queued']).' театъра')->success();
                if ($result['skipped'] !== []) {
                    $note->body('Вече текат: '.implode(', ', $result['skipped']));
                }
                $note->send();
            });
    }
}
