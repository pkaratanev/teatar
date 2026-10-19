<?php

namespace App\Filament\Widgets;

use App\Services\SyncAlerts as Alerts;
use Filament\Widgets\Widget;

/** Above the sync table: only shown when a theatre's sync needs a look. */
class SyncAlerts extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.sync-alerts';

    public static function canView(): bool
    {
        return Alerts::count() > 0;
    }

    /** @return list<array{theatre: \App\Models\Theatre, reasons: list<string>}> */
    public function getAlerts(): array
    {
        return Alerts::current();
    }
}
