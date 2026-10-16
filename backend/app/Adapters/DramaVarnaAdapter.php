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
 * Драматичен театър „Стоян Бъчваров“ – Варна (Joomla calendar).
 * {website}/index.php/bg/program-and-tickets?month=M&year=Y lists one month, "Представления във
 * Варна" by default (tours are a separate filter). Each day is <li id="li-YYYY-MM-DD"> with one
 * .event-block per performance: stage, "19:00 - 21:00", title link (?event=N), author line,
 * director, and ticket providers (ФКЦ, Eventim, Grabo).
 */
class DramaVarnaAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://dramavarna.com';

    public const MONTHS = 3;

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/index.php/bg/program-and-tickets?month={$month->month}&year={$month->year}";
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
    public function parse(string $html, ?int $year = null, ?int $month = null): array
    {
        $crawler = new Crawler($html);
        $heading = $crawler->filter('#calendar .current-month');

        if ($heading->count() === 0) {
            throw new RuntimeException('Calendar (#calendar .current-month) not found; the site layout may have changed.');
        }

        // Asked for a month the site doesn't serve? Then the heading names another one: take nothing.
        if ($year !== null && $month !== null && ! self::headingIs($heading->text(), $year, $month)) {
            return [];
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('#calendar ul.dates > li') as $day) {
            $day = new Crawler($day);
            if (! preg_match('/^li-(\d{4})-(\d{2})-(\d{2})$/', (string) $day->attr('id'), $d)) {
                continue;
            }

            foreach ($day->filter('.event-block') as $block) {
                $block = new Crawler($block);
                $link = $block->filter('a.name');
                $time = $block->filter('.time');
                if ($link->count() === 0 || $time->count() === 0 || ! preg_match('/(\d{1,2})[:.](\d{2})/', $time->text(), $t)) {
                    continue;
                }

                $performances[] = new ScrapedPerformance(
                    title: ProgrammeImporter::cleanTitle($link->text()),
                    startsAt: CarbonImmutable::create((int) $d[1], (int) $d[2], (int) $d[3], (int) $t[1], (int) $t[2], 0, $tz),
                    stage: self::text($block, '.place'),
                    ticketUrl: self::ticketUrl($block),
                    sourceUrl: (string) $link->attr('href') ?: null,
                    author: self::author($block),
                    director: self::director($block),
                );
            }
        }

        return $performances;
    }

    private static function headingIs(string $heading, int $year, int $month): bool
    {
        if (! preg_match('/(\p{L}+)\s+(\d{4})/u', $heading, $m)) {
            return false;
        }

        return (int) $m[2] === $year && BulgarianDate::month($m[1]) === $month;
    }

    private static function text(Crawler $node, string $selector): ?string
    {
        $found = $node->filter($selector);
        $text = $found->count() ? trim(preg_replace('/\s+/u', ' ', $found->first()->text()) ?? '') : '';

        return $text === '' ? null : $text;
    }

    /** "от Бертолт Брехт", "по романа на …", "комедия от Лопе де Вега": kept as printed. */
    private static function author(Crawler $block): ?string
    {
        $line = self::text($block, '.first-line > div');

        return $line !== null && preg_match('/(^|\s)(от|по)\s/u', $line) ? $line : null;
    }

    private static function director(Crawler $block): ?string
    {
        $line = self::text($block, '.categories');

        return $line !== null && preg_match('/^Режисьор:\s*(.+)$/u', $line, $m) ? trim($m[1]) : null;
    }

    /** The first ticket provider's link; some hrefs carry junk before the URL ("/ https://…"). */
    private static function ticketUrl(Crawler $block): ?string
    {
        foreach ($block->filter('.providers a') as $a) {
            if (preg_match('~https?://\S+~', (string) $a->getAttribute('href'), $m)) {
                return $m[0];
            }
        }

        return null;
    }
}
