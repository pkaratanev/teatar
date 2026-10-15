<?php

namespace App\Adapters\Contracts;

use App\Adapters\ScrapedPerformance;
use App\Models\Theatre;

/**
 * Reads one theatre's programme from a source the theatre controls (its own
 * website, a feed or a spreadsheet it sends us) and returns plain facts:
 * title, date, time, stage, ticket link. Never point an adapter at
 * theatre.art.bg — see docs/plan.md, "Data".
 */
interface TheatreAdapter
{
    /**
     * @return iterable<ScrapedPerformance>
     */
    public function fetch(Theatre $theatre): iterable;
}
