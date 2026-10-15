<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Сатиричен театър „Алеко Константинов“ — a Next.js site. The page
 * {website}/program?f:month=YYYY-MM is rendered on the server and carries the
 * whole month as JSON in <script id="__NEXT_DATA__">: exact UTC start time,
 * stage key, production page, prices and ticket links.
 */
class SatirataAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://satirata.bg';

    public const MONTHS = 3;

    public const STAGES = [
        'stage-main' => 'Голяма сцена',
        'stage-chamber' => 'Камерна зала „Методи Андонов“',
        'stage-bar' => 'Сцена „Comedy Bar Happy Сатира“',
    ];

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/program?f:month={$month->format('Y-m')}";
            $response = TheatreHttp::client()->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            yield from $this->parse($response->body(), $month->year, $month->month, $base);
        }
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, int $year, int $month, string $base = self::DEFAULT_BASE_URL): array
    {
        if (! preg_match('#<script id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $html, $m)) {
            throw new RuntimeException('__NEXT_DATA__ not found; the site layout may have changed.');
        }

        $data = json_decode($m[1], true);
        $list = $data['props']['pageProps']['prefetchedData']['eventsData']['events']['list'] ?? null;
        if (! is_array($list)) {
            throw new RuntimeException('Events list missing from __NEXT_DATA__; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($list as $event) {
            $startsAt = $event['startsAt'] ?? $event['settings']['starts_at'] ?? null;
            if (! is_string($startsAt) || ($event['visibility'] ?? 'public') !== 'public') {
                continue;
            }

            $local = CarbonImmutable::parse($startsAt)->setTimezone($tz);
            // If the filter is ever ignored, the page shows another month; keep only the one asked for.
            if ($local->year !== $year || $local->month !== $month) {
                continue;
            }

            [$title, $age] = self::splitAge((string) ($event['title'] ?? ''));
            [$priceFrom, $priceTo] = self::prices((string) ($event['settings']['custom']['price'] ?? ''));
            $page = $event['settings']['eventPage'] ?? null;

            $performances[] = new ScrapedPerformance(
                title: $title,
                startsAt: $local,
                stage: self::STAGES[$event['settings']['stage'] ?? ''] ?? null,
                ticketUrl: $event['settings']['ticketUrlNoRegistration'] ?? $event['settings']['ticketUrlRegistration'] ?? null,
                priceFrom: $priceFrom,
                priceTo: $priceTo,
                sourceUrl: $page ? "{$base}/{$page}" : null,
                ageAdvisory: $age,
            );
        }

        return $performances;
    }

    /**
     * "Луди за връзване 16+  " → ["Луди за връзване", "16+"]
     *
     * @return array{0: string, 1: string|null}
     */
    public static function splitAge(string $title): array
    {
        $title = trim($title);
        if (preg_match('/^(.*?)\s+(\d{1,2}\+)$/u', $title, $m)) {
            return [ProgrammeImporter::cleanTitle($m[1]), $m[2]];
        }

        return [ProgrammeImporter::cleanTitle($title), null];
    }

    /**
     * "35.20 лв./18.00 €, 58.67 лв./30.00 €" → [18.0, 30.0] (euro amounts only)
     *
     * @return array{0: float|null, 1: float|null}
     */
    public static function prices(string $text): array
    {
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*€/u', $text, $m);
        $euros = array_map(fn ($v) => (float) str_replace(',', '.', $v), $m[1]);

        return $euros === [] ? [null, null] : [min($euros), max($euros)];
    }
}
