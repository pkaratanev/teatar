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
 * Театър „София“ — {website}/program?year=Y&month=M lists the whole month.
 * Rows show only day + weekday, so month and year come from the request
 * (checked against the selected month on the page). Sold-out rows carry no
 * time or stage and are skipped. Some shows play at another venue
 * ("Театър СЪЛЗА И СМЯХ"); that venue becomes the stage.
 */
class SofiaTheatreAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://sofiatheatre.eu';

    public const MONTHS = 3;

    private const OWN_PREFIX = '/^Театър\s+София\s*[-–]\s*/iu';

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/program?year={$month->year}&month={$month->month}";
            $response = TheatreHttp::client()->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            yield from $this->parse($response->body(), $month->year, $month->month);
        }
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, int $year, int $month): array
    {
        $crawler = new Crawler($html);

        if ($crawler->filter('#shows-container')->count() === 0) {
            // An unpublished month renders without the container ("Няма намерени … представления").
            if (str_contains($html, 'Няма намерени')) {
                return [];
            }

            throw new RuntimeException('Programme list (#shows-container) not found; the site layout may have changed.');
        }

        $selected = $crawler->filter('#change_date option[selected]');
        if ($selected->count() && ((int) $selected->attr('value') !== $month || (int) $selected->attr('data-year') !== $year)) {
            throw new RuntimeException("Asked for {$month}/{$year} but the page shows {$selected->attr('value')}/{$selected->attr('data-year')}.");
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('#shows-container > div.box-shadow-default') as $node) {
            $row = new Crawler($node);
            $cols = $row->filter('.row > div');
            $time = $row->filter('.col-xl-1 a')->reduce(fn (Crawler $a) => (bool) preg_match('/^\s*\d{1,2}:\d{2}\s*$/', $a->text()));

            if ($time->count() === 0 || $cols->count() < 5) {
                continue; // sold out: no time or stage shown
            }

            $day = (int) trim($row->filter('.day-program')->text());
            [$hour, $minute] = BulgarianDate::time($time->text());

            $titleBlock = $row->filter('.font-24');
            $author = $titleBlock->count() ? self::author($titleBlock) : null;
            $link = $row->filter('a[href*="/repertoires/"]');
            $ticket = $row->filter('a.buy-button');
            $venue = trim($cols->eq(3)->text());

            $performances[] = new ScrapedPerformance(
                title: ProgrammeImporter::cleanTitle($titleBlock->text()),
                startsAt: CarbonImmutable::create($year, $month, $day, $hour, $minute, 0, $tz),
                stage: self::stage($venue),
                ticketUrl: $ticket->count() ? (string) $ticket->attr('href') : null,
                sourceUrl: $link->count() ? (string) $link->attr('href') : null,
                author: $author,
            );
        }

        return $performances;
    }

    /** The line after the title: "от Жан Кокто" → "Жан Кокто"; "по Карло Колоди" stays. */
    private static function author(Crawler $title): ?string
    {
        $next = $title->nextAll()->first();
        if ($next->count() === 0) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $next->text()) ?? '');
        $text = preg_replace('/^от\s+/u', '', $text) ?? $text;

        return $text !== '' ? $text : null;
    }

    /** "Театър София - Камерна сцена" → "Камерна сцена"; another venue keeps its name. */
    private static function stage(string $venue): ?string
    {
        $venue = trim(preg_replace('/\s+/u', ' ', $venue) ?? $venue);
        if ($venue === '') {
            return null;
        }

        if (preg_match(self::OWN_PREFIX, $venue)) {
            return preg_replace(self::OWN_PREFIX, '', $venue);
        }

        // "Театър СЪЛЗА И СМЯХ" → "Театър „Сълза и смях“"
        if (preg_match('/^Театър\s+(.+)$/iu', $venue, $m) && mb_strtoupper($m[1]) === $m[1]) {
            $name = mb_strtolower($m[1]);

            return 'Театър „'.mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1).'“';
        }

        return $venue;
    }
}
