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
 * Театър 199 „Валентин Стойчев“ — {website}/bg/schedule?page=N&type= pages
 * through all upcoming performances (one hall). Two layouts: a featured block
 * ("Днес, четвъртък, 15 октомври" + time) and cards ("25.10" + time).
 * No year anywhere, so it is inferred. Stops at the first empty page.
 */
class Theatre199Adapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://theatre199.org';

    public const MAX_PAGES = 12;

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $today = CarbonImmutable::now(config('app.theatre_timezone'));

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $url = "{$base}/bg/schedule?page={$page}&type=";
            $response = TheatreHttp::client()->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            $rows = $this->parse($response->body(), $today);
            if ($rows === []) {
                break;
            }

            yield from $rows;
        }
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, CarbonImmutable $today): array
    {
        $crawler = new Crawler($html);

        if ($crawler->filter('#main')->count() === 0) {
            throw new RuntimeException('Main content (#main) not found; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        // Featured block: "Днес, четвъртък, 15 октомври" + <span class="list-time">.
        foreach ($crawler->filter('ul.calendar-list li') as $node) {
            $li = new Crawler($node);
            $article = $li->closest('article');
            $title = $article?->filter('h2 a, h3 a, h4 a');
            if (! $title || $title->count() === 0 || ! preg_match('/(\d{1,2})\s+(\p{L}+)/u', $li->text(), $d)) {
                continue;
            }

            $performances[] = $this->performance(
                (int) $d[1], BulgarianDate::month($d[2]), BulgarianDate::weekday($li->text()),
                $li->filter('.list-time')->text(''), $title, $today, $tz,
            );
        }

        // Cards: <span class="date">25.10</span><span class="list-time">19:30</span>.
        foreach ($crawler->filter('div.card-list') as $node) {
            $card = new Crawler($node);
            $date = $card->filter('.date')->reduce(fn (Crawler $c) => (bool) preg_match('/^\s*\d{1,2}\.\d{1,2}\s*$/', $c->text()));
            $title = $card->filter('h4 a, h3 a');
            if ($date->count() === 0 || $title->count() === 0) {
                continue;
            }

            [$day, $month] = array_map('intval', explode('.', trim($date->text())));
            $performances[] = $this->performance($day, $month, null, $card->filter('.list-time')->text(''), $title, $today, $tz);
        }

        return $performances;
    }

    private function performance(int $day, int $month, ?int $weekday, string $time, Crawler $title, CarbonImmutable $today, string $tz): ScrapedPerformance
    {
        [$hour, $minute] = BulgarianDate::time($time);
        $year = BulgarianDate::inferYear($day, $month, $weekday, $today);

        return new ScrapedPerformance(
            title: ProgrammeImporter::cleanTitle($title->text()),
            startsAt: CarbonImmutable::create($year, $month, $day, $hour, $minute, 0, $tz),
            sourceUrl: (string) $title->attr('href'),
        );
    }
}
