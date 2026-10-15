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
        // Guest performances: a visiting company plays at this theatre. The company
        // name is optional (some sites only label the show as a guest).
        public ?string $visitingCompany = null,
        public bool $isGuest = false,
        public ?string $author = null,
    ) {}

    public function guest(): bool
    {
        return $this->isGuest || $this->visitingCompany !== null;
    }
}
