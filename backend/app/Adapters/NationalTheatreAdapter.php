<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use App\Support\AbsoluteUrl;
use App\Support\BulgarianDate;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Народен театър „Иван Вазов“ — reads {website}/bg/programa?day=&month=M&year=Y
 * (UTF-8, one block per day, several shows per day, stage on each show).
 * Takes title, author line, date, time, stage, guest label, poster and the ticket link.
 */
class NationalTheatreAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://nationaltheatre.bg';

    public const MONTHS = 3;

    /** Guided tours of the building are listed like performances; they are not theatre. */
    private const SKIP_STAGES = ['Из театъра и около него'];

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/bg/programa?day=&month={$month->month}&year={$month->year}";
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
        $crawler = new Crawler($html);
        $days = $crawler->filter('section.calendar div.items > div.item');

        // A month with nothing scheduled yet still has the calendar section.
        if ($days->count() === 0 && $crawler->filter('section.calendar')->count() === 0) {
            throw new RuntimeException('Programme calendar (section.calendar) not found; the site layout may have changed.');
        }

        // The page heading ("ноември 2026") must match the month asked for.
        $heading = $crawler->filter('section.calendar .month-title h2');
        if ($heading->count() && preg_match('/(\p{L}+)\s*(\d{4})/u', trim($heading->text()), $m)
            && (BulgarianDate::month($m[1]) !== $month || (int) $m[2] !== $year)) {
            throw new RuntimeException("Asked for {$month}/{$year} but the page shows \"{$heading->text()}\".");
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($days as $dayNode) {
            $day = new Crawler($dayNode);
            $dayNumber = (int) trim($day->filter('.date .number')->text());
            $dayMonth = BulgarianDate::month($day->filter('.date .month-name')->text());

            foreach ($day->filter('div.show') as $showNode) {
                $show = new Crawler($showNode);
                $stage = self::squish($show->filter('.details .stage')->count() ? $show->filter('.details .stage')->text() : '');

                if (in_array($stage, self::SKIP_STAGES, true)) {
                    continue;
                }

                $link = $show->filter('.title h2 a');
                if ($link->count() === 0) {
                    continue;
                }

                $ticket = $show->filter('a.btn-tickets');
                $ticketUrl = $ticket->count() ? (string) $ticket->attr('href') : null;

                $performances[] = new ScrapedPerformance(
                    title: ProgrammeImporter::cleanTitle($link->text()),
                    startsAt: self::startsAt($ticketUrl, $year, $dayMonth, $dayNumber, $show, $tz),
                    stage: $stage ?: null,
                    ticketUrl: $ticketUrl ? self::encodeTicketUrl($ticketUrl) : null,
                    sourceUrl: (string) $link->attr('href'),
                    isGuest: $show->filter('.guest-label')->count() > 0,
                    author: self::author($show),
                    posterUrl: AbsoluteUrl::from($show->filter('a.poster img')->count() ? $show->filter('a.poster img')->attr('src') : null, $base),
                );
            }
        }

        return $performances;
    }

    /**
     * The ticket link carries the exact date and time (".../2026-11-10|19:00:00");
     * fall back to the printed day and hour when a show has no ticket link.
     */
    private static function startsAt(?string $ticketUrl, int $year, int $month, int $day, Crawler $show, string $tz): CarbonImmutable
    {
        if ($ticketUrl && preg_match('#/(\d{4}-\d{2}-\d{2})\|(\d{2}:\d{2})#', rawurldecode($ticketUrl), $m)) {
            return CarbonImmutable::createFromFormat('Y-m-d H:i', "{$m[1]} {$m[2]}", $tz);
        }

        [$hour, $minute] = BulgarianDate::time($show->filter('.details .hour')->text());

        // Each page holds exactly one month, so the page's year applies to every day on it.
        return CarbonImmutable::create($year, $month, $day, $hour, $minute, 0, $tz);
    }

    /** "от Ив Жамиак" → "Ив Жамиак"; "по романа на …" is kept as written. */
    private static function author(Crawler $show): ?string
    {
        $node = $show->filter('.title .author');
        if ($node->count() === 0) {
            return null;
        }

        $text = self::squish($node->text());
        $text = preg_replace('/^от\s+/u', '', $text) ?? $text;

        return $text !== '' ? $text : null;
    }

    private static function encodeTicketUrl(string $url): string
    {
        return str_replace('|', '%7C', $url);
    }

    private static function squish(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
