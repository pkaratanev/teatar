<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Support\BulgarianDate;
use App\Support\GuestTitle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Младежки театър „Николай Бинев“ — {website}/programa.php?year=Y&month=M
 * (windows-1251). One table row per performance: "dd/mm", time, title, hall;
 * the row's data-href is the production page. An unpublished month quietly
 * returns another month, so the "Програма <месец> <година>" heading is checked.
 */
class MladezhkiAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://mlt.bg';

    public const MONTHS = 3;

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/programa.php?year={$month->year}&month={$month->month}";
            $response = Http::timeout(20)->retry(2, 1000)->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            $body = $response->body();
            if (! preg_match('/charset=utf-8/i', (string) $response->header('Content-Type'))) {
                $body = mb_convert_encoding($body, 'UTF-8', 'Windows-1251');
            }

            yield from $this->parse($body, $month->year, $month->month, $base);
        }
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, int $year, int $month, string $base = self::DEFAULT_BASE_URL): array
    {
        $crawler = new Crawler($html);
        $heading = $crawler->filter('h1.title-page');

        if ($heading->count() === 0) {
            throw new RuntimeException('Programme heading (h1.title-page) not found; the site layout may have changed.');
        }

        if (! preg_match('/(\p{L}+)\s+(\d{4})/u', $heading->text(), $m)
            || BulgarianDate::month($m[1]) !== $month || (int) $m[2] !== $year) {
            // Not published yet: the site shows another month instead of an empty one.
            return [];
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('table.programa tbody tr') as $node) {
            $row = new Crawler($node);
            $cells = $row->filter('td');
            if ($cells->count() < 3 || ! preg_match('#(\d{1,2})/(\d{1,2})#', $cells->eq(0)->text(), $d)) {
                continue;
            }

            [$hour, $minute] = BulgarianDate::time($cells->eq(1)->text());
            [$title, $visiting] = GuestTitle::split($cells->eq(2)->text());
            $href = (string) $row->attr('data-href');

            $performances[] = new ScrapedPerformance(
                title: $title,
                startsAt: CarbonImmutable::create($year, (int) $d[2], (int) $d[1], $hour, $minute, 0, $tz),
                stage: $cells->count() > 3 ? (trim($cells->eq(3)->text()) ?: null) : null,
                sourceUrl: $href !== '' ? $base.'/'.ltrim($href, '/') : null,
                visitingCompany: $visiting,
            );
        }

        return $performances;
    }
}
