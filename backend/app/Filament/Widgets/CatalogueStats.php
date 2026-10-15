<?php

namespace App\Filament\Widgets;

use App\Enums\CreditRole;
use App\Filament\Resources\AdapterRuns\AdapterRunResource;
use App\Filament\Resources\Productions\ProductionResource;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Production;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CatalogueStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $upcoming = Performance::upcoming()->where('starts_at', '<=', now()->addDays(30))->count();
        $theatresAhead = Performance::upcoming()->where('starts_at', '<=', now()->addDays(30))
            ->join('productions', 'productions.id', '=', 'performances.production_id')
            ->distinct()->count('productions.theatre_id');

        $playing = Production::whereHas('performances', fn (Builder $q) => $q->upcoming());
        $playingCount = (clone $playing)->count();
        $noDirector = (clone $playing)->whereDoesntHave('credits', fn (Builder $q) => $q->where('role_type', CreditRole::Director))->count();
        $noPoster = (clone $playing)->whereDoesntHave('media', fn (Builder $q) => $q->where('collection_name', 'poster'))->count();

        $failed = AdapterRun::where('status', 'failed')->where('started_at', '>=', now()->subDay())->count();

        return [
            Stat::make('Представления в следващите 30 дни', $upcoming)
                ->description("в {$theatresAhead} театъра")
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Постановки с предстоящи дати', $playingCount)
                ->description("{$noDirector} без режисьор · {$noPoster} без плакат")
                ->descriptionColor($noDirector + $noPoster > 0 ? 'warning' : 'success')
                ->url(ProductionResource::getUrl('index'))
                ->icon(Heroicon::OutlinedTicket),
            Stat::make('Неуспешни синхронизации (24 ч.)', $failed)
                ->description($failed > 0 ? 'Вижте журнала' : 'Всичко е наред')
                ->descriptionColor($failed > 0 ? 'danger' : 'success')
                ->url(AdapterRunResource::getUrl('index'))
                ->icon(Heroicon::OutlinedArrowPath),
        ];
    }
}
