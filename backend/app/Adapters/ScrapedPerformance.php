<?php

namespace App\Adapters;

use Carbon\CarbonImmutable;

/**
 * One performance as an adapter found it, before it is matched to a production.
 */
final readonly class ScrapedPerformance
{
    public function __construct(
        public string $title,
        public CarbonImmutable $startsAt,
        public ?string $stage = null,
        public ?string $ticketUrl = null,
        public ?float $priceFrom = null,
        public ?float $priceTo = null,
        public ?string $sourceUrl = null,
    ) {}
}
