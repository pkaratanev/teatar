<?php

namespace App\Filament\Widgets;

use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** One row per theatre with an adapter: did last night's sync work, and how far ahead do we know the programme? */
class SyncHealth extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $lastRun = fn (string $column) => AdapterRun::select($column)
            ->whereColumn('adapter_runs.theatre_id', 'theatres.id')
            ->latest('started_at')
            ->limit(1);

        return $table
            ->heading('Синхронизация по театри')
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
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'succeeded' => 'OK',
                        'failed' => 'Грешка',
                        'running' => 'Тече',
                        default => 'Не е пускана',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (Theatre $record) => $record->getAttribute('last_error')),
                TextColumn::make('last_started_at')->label('Кога')->since(),
                TextColumn::make('last_found')->label('Намерени'),
                TextColumn::make('upcoming_count')->label('Предстоящи'),
                TextColumn::make('last_performance_at')->label('Програма до')
                    ->date('d.m.Y', config('app.theatre_timezone')),
            ])
            ->recordActions([
                Action::make('sync')->label('Обнови')->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Theatre $record) {
                        SyncTheatreProgramme::dispatch($record);
                        Notification::make()->title("Синхронизацията на {$record->name} е пусната")->success()->send();
                    }),
            ]);
    }
}
