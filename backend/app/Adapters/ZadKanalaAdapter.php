<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use App\Support\BulgarianDate;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * МГТ „Зад канала“ — Drupal calendar view at {website}/programa/YYYY-MM.
 * Each row has a machine-readable date (content="2026-11-01T19:00:00+02:00")
 * and a link to the production page. One stage; the "buy" button is a popup,
 * so tickets go through the theatre's box-office link.
 */
class ZadKanalaAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://zadkanala.bg';

    public const MONTHS = 3;

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/programa/{$month->format('Y-m')}";
            $response = TheatreHttp::client()->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            yield from $this->parse($response->body(), $base);
        }
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, string $base = self::DEFAULT_BASE_URL): array
    {
        $crawler = new Crawler($html);

        if ($crawler->filter('div.view-calendar')->count() === 0) {
            throw new RuntimeException('Calendar view (div.view-calendar) not found; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('div.view-calendar tbody tr') as $node) {
            $row = new Crawler($node);
            $date = $row->filter('.views-field-field-dates .date-display-single');
            $link = $row->filter('.views-field-title a');
            if ($date->count() === 0 || $link->count() === 0) {
                continue;
            }

            $performances[] = new ScrapedPerformance(
                title: ProgrammeImporter::cleanTitle($link->text()),
                startsAt: self::startsAt($date, $tz),
                sourceUrl: $base.'/'.ltrim((string) $link->attr('href'), '/'),
            );
        }

        return $performances;
    }

    private static function startsAt(Crawler $date, string $tz): CarbonImmutable
    {
        $iso = (string) $date->attr('content');
        if ($iso !== '') {
            return CarbonImmutable::parse($iso)->setTimezone($tz);
        }

        // Fallback to the printed "1 Ноем. 2026 - 19:00".
        if (! preg_match('/(\d{1,2})\s+(\p{L}+)\.?\s+(\d{4})\s*-\s*(\d{1,2})[.:](\d{2})/u', $date->text(), $m)) {
            throw new RuntimeException("Unrecognised date: {$date->text()}");
        }

        return CarbonImmutable::create((int) $m[3], BulgarianDate::month($m[2]), (int) $m[1], (int) $m[4], (int) $m[5], 0, $tz);
    }
}
