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
 * Театър „Възраждане“ — WordPress page fed by Entase:
 * {website}/programa/?date=YYYYMMDD-YYYYMMDD. Each event card has title,
 * "Автор / Режисьор" line, "03 Ное" + weekday, time(s), and the Entase event id.
 * One venue, no halls. Guest shows carry the category class "category-gostuvashto".
 */
class VazrajdaneAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://theatrevazrajdane.bg';

    public const MONTHS = 3;

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/programa/?date={$month->format('Ymd')}-{$month->endOfMonth()->format('Ymd')}";
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

        if ($crawler->filter('div.event_items')->count() === 0) {
            throw new RuntimeException('Event list (div.event_items) not found; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($crawler->filter('div.event_items > div.event_item') as $node) {
            $item = new Crawler($node);
            $title = $item->filter('.event_entase_title');
            $date = $item->filter('.event_entase_dateonly p');
            if ($title->count() === 0 || $date->count() === 0) {
                continue;
            }

            if (! preg_match('/(\d{1,2})\s+(\p{L}+)/u', $date->first()->text(), $d)) {
                throw new RuntimeException("Unrecognised date: {$date->first()->text()}");
            }
            $day = (int) $d[1];
            $itemMonth = BulgarianDate::month($d[2]);
            $itemYear = $itemMonth < $month ? $year + 1 : $year;

            $credits = self::credits($item);
            $link = $item->filter('a[href*="/portfolio/"]');
            $eventId = $item->filter('[data-event]');

            // An entry can list several times: "11:00 ч. и 12:30 ч."
            preg_match_all('/(\d{1,2})[.:](\d{2})/', $item->filter('.event_entase_timeonly')->text(''), $times, PREG_SET_ORDER);

            foreach ($times as [, $hour, $minute]) {
                $performances[] = new ScrapedPerformance(
                    title: ProgrammeImporter::cleanTitle($title->text()),
                    startsAt: CarbonImmutable::create($itemYear, $itemMonth, $day, (int) $hour, (int) $minute, 0, $tz),
                    ticketUrl: $eventId->count() ? 'https://www.entase.com/book?eid='.$eventId->attr('data-event') : null,
                    sourceUrl: $link->count() ? (string) $link->attr('href') : null,
                    isGuest: str_contains((string) $item->attr('class'), 'category-gostuvashto'),
                    author: $credits['автор'] ?? null,
                    director: $credits['режисьор'] ?? null,
                );
            }
        }

        return $performances;
    }

    /**
     * "Автор - Валентин Красногоров <br> Режисьор: Михаил Милчев" → ['автор' => …, 'режисьор' => …]
     *
     * @return array<string, string>
     */
    private static function credits(Crawler $item): array
    {
        $node = $item->filter('.event_production_post_content');
        if ($node->count() === 0) {
            return [];
        }

        $credits = [];
        foreach (preg_split('/<br\s*\/?>|\n/i', $node->html()) ?: [] as $line) {
            $line = trim(html_entity_decode(strip_tags($line)));
            if (preg_match('/^(\p{L}+)\s*[-–:]\s*(.+)$/u', $line, $m)) {
                $credits[mb_strtolower($m[1])] = trim($m[2]);
            }
        }

        return $credits;
    }
}
