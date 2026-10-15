<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use App\Support\BulgarianDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Открита сцена „Сълза и смях“ — two sources joined on the ticket id:
 *  - {website}/site/calendar/YYYY/MM: a month grid; each event is "19:00 - ЗАГЛАВИЕ"
 *    linking to /site/selector/{ticketId}. Full dates, but no stage.
 *  - {website}/site/events/{n}: one list per stage; each card links the same
 *    /site/selector/{ticketId} and the production page /site/play/{id}.
 * Events missing from the stage lists are imported without a stage.
 */
class SalzaISmyahAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://www.salzaismyah.bg';

    public const MONTHS = 3;

    /** Stage list number → stage name. */
    public const STAGES = [
        1 => 'Открита сцена',
        2 => 'Камерна сцена „Славянска беседа“',
        3 => 'Ъндърграунд сцена',
    ];

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');

        $stageLists = [];
        foreach (array_keys(self::STAGES) as $n) {
            $stageLists[$n] = $this->get("{$base}/site/events/{$n}");
        }
        $index = $this->indexStageLists($stageLists, $base);

        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();
        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            yield from $this->parseCalendar($this->get("{$base}/site/calendar/{$month->format('Y/m')}"), $month->year, $month->month, $index, $base);
        }
    }

    /**
     * Map each ticket id to its stage and production page.
     *
     * @param  array<int, string>  $stageLists  stage number → HTML of /site/events/{n}
     * @return array<string, array{stage: string, play: string|null}>
     */
    public function indexStageLists(array $stageLists, string $base = self::DEFAULT_BASE_URL): array
    {
        $index = [];

        foreach ($stageLists as $n => $html) {
            foreach ((new Crawler($html))->filter('div.mb-5') as $node) {
                $card = new Crawler($node);
                $selector = $card->filter('a[href*="/site/selector/"]');
                if ($selector->count() === 0 || ! preg_match('#/site/selector/(\d+)#', (string) $selector->attr('href'), $m)) {
                    continue; // box-office-only shows have no ticket id
                }
                $play = $card->filter('a[href*="/site/play/"]');

                $index[$m[1]] = [
                    'stage' => self::STAGES[$n] ?? "Сцена {$n}",
                    'play' => $play->count() ? $base.'/'.ltrim((string) $play->attr('href'), '/') : null,
                ];
            }
        }

        return $index;
    }

    /**
     * @param  array<string, array{stage: string, play: string|null}>  $index
     * @return list<ScrapedPerformance>
     */
    public function parseCalendar(string $html, int $year, int $month, array $index = [], string $base = self::DEFAULT_BASE_URL): array
    {
        $crawler = new Crawler($html);

        if ($crawler->filter('div.calendar')->count() === 0) {
            throw new RuntimeException('Calendar (div.calendar) not found; the site layout may have changed.');
        }

        // The heading ("Ноември 2026") must match the month asked for.
        $heading = $crawler->filter('div.calendar span.h3');
        if ($heading->count() && preg_match('/(\p{L}+)[\s\x{00A0}]+(\d{4})/u', $heading->text(), $m)
            && (BulgarianDate::month($m[1]) !== $month || (int) $m[2] !== $year)) {
            throw new RuntimeException("Asked for {$month}/{$year} but the page shows \"{$heading->text()}\".");
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('div.calendar div.cday') as $node) {
            $cell = new Crawler($node);
            if (str_contains((string) $cell->attr('class'), 'other-month') || $cell->filter('.day')->count() === 0) {
                continue; // padding days from the previous / next month
            }
            $day = (int) trim($cell->filter('.day')->text());

            foreach ($cell->filter('.content-row a') as $linkNode) {
                $link = new Crawler($linkNode);
                if (! preg_match('/^\s*(\d{1,2})[.:](\d{2})\s*-\s*(.+)$/u', $link->text(), $e)) {
                    continue;
                }

                $href = (string) $link->attr('href');
                $ticketId = preg_match('#/site/selector/(\d+)#', $href, $t) ? $t[1] : null;
                $known = $ticketId !== null ? ($index[$ticketId] ?? null) : null;

                $performances[] = new ScrapedPerformance(
                    title: ProgrammeImporter::cleanTitle($e[3]),
                    startsAt: CarbonImmutable::create($year, $month, $day, (int) $e[1], (int) $e[2], 0, $tz),
                    stage: $known['stage'] ?? null,
                    ticketUrl: $href !== '' ? $href : null,
                    sourceUrl: $known['play'] ?? "{$base}/site/calendar/".sprintf('%d/%02d', $year, $month),
                );
            }
        }

        return $performances;
    }

    private function get(string $url): string
    {
        $response = Http::timeout(20)->retry(2, 1000)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("{$url} answered HTTP {$response->status()}");
        }

        return $response->body();
    }
}
