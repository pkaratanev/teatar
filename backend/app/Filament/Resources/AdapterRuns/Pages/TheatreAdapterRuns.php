<?php

namespace App\Filament\Resources\AdapterRuns\Pages;

use App\Filament\Resources\AdapterRuns\AdapterRunResource;
use App\Filament\Widgets\SyncHealth;
use App\Models\Theatre;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Every sync run of one theatre, newest first, opened from a row of the sync log. */
class TheatreAdapterRuns extends ListRecords
{
    protected static string $resource = AdapterRunResource::class;

    public int $theatreId;

    public function mount(int|string|null $theatre = null): void
    {
        $this->theatreId = Theatre::query()->findOrFail($theatre)->getKey();

        parent::mount();
    }

    public function getTheatre(): Theatre
    {
        return Theatre::query()->findOrFail($this->theatreId);
    }

    public function getTitle(): string
    {
        return 'Синхронизации: '.$this->getTheatre()->name;
    }

    /** @return array<string, string> */
    public function getBreadcrumbs(): array
    {
        return [AdapterRunResource::getUrl() => 'Синхронизации', $this->getTheatre()->name];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('theatre_id', $this->theatreId))
            ->defaultSort('started_at', 'desc')
            ->filters([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncTheatre')
                ->label('Обнови сега')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn () => SyncHealth::syncOne($this->getTheatre())),
        ];
    }
}
