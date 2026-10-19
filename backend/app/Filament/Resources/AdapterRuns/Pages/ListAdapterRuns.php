<?php

namespace App\Filament\Resources\AdapterRuns\Pages;

use App\Filament\Resources\AdapterRuns\AdapterRunResource;
use App\Filament\Widgets\SyncHealth;
use App\Models\AdapterRun;
use App\Models\Theatre;
use App\Support\SyncSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The sync log's front page: one row per active theatre, its latest run. A row opens that
 * theatre's full history (TheatreAdapterRuns); "Обнови" starts a sync for just that theatre.
 */
class ListAdapterRuns extends ListRecords
{
    protected static string $resource = AdapterRunResource::class;

    public function getTitle(): string
    {
        return 'Синхронизации';
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereIn('id', AdapterRun::query()->selectRaw('max(id)')->groupBy('theatre_id'))
                ->whereHas('theatre', fn (Builder $theatres) => $theatres->where('is_active', true)))
            ->defaultSort(fn (Builder $query) => $query->orderBy(Theatre::query()->select('name')->whereColumn('theatres.id', 'adapter_runs.theatre_id')))
            ->recordUrl(fn (AdapterRun $record) => AdapterRunResource::getUrl('theatre', ['theatre' => $record->theatre_id]))
            ->recordActions([
                Action::make('syncTheatre')
                    ->label('Обнови')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->action(fn (AdapterRun $record) => SyncHealth::syncOne($record->theatre)),
                Action::make('history')
                    ->label('История')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->color('gray')
                    ->url(fn (AdapterRun $record) => AdapterRunResource::getUrl('theatre', ['theatre' => $record->theatre_id])),
            ])
            ->filters([]);
    }

    public function getSubheading(): ?string
    {
        return SyncSchedule::summary();
    }

    protected function getHeaderActions(): array
    {
        return [
            SyncHealth::syncAllAction(),
            Action::make('schedule')
                ->label('График')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->modalHeading('Нощна синхронизация')
                ->modalSubmitActionLabel('Запази')
                ->modalDescription('Важи за всички театри. „Активен“ на всеки театър и ръчното „Обнови“ работят както досега.')
                ->fillForm(fn () => ['time' => SyncSchedule::time(), 'paused' => SyncSchedule::paused()])
                ->schema([
                    TextInput::make('time')
                        ->label('Час (София)')
                        ->placeholder(SyncSchedule::DEFAULT_TIME)
                        ->helperText('ЧЧ:ММ, напр. 03:15')
                        ->required()
                        ->regex(SyncSchedule::TIME_PATTERN),
                    Toggle::make('paused')->label('Пауза: не пускай нощната синхронизация'),
                ])
                ->action(function (array $data) {
                    SyncSchedule::save($data['time'], (bool) $data['paused']);
                    Notification::make()->title('Графикът е запазен')->body(SyncSchedule::summary())->success()->send();
                }),
        ];
    }
}
