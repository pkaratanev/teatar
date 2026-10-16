<?php

namespace App\Filament\Resources\AdapterRuns\Pages;

use App\Filament\Resources\AdapterRuns\AdapterRunResource;
use App\Filament\Widgets\SyncHealth;
use App\Support\SyncSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAdapterRuns extends ListRecords
{
    protected static string $resource = AdapterRunResource::class;

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
