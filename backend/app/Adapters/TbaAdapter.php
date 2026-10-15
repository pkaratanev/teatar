<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Support\Cyrillic;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Театър „Българска армия“ — reads the theatre's own monthly programme at
 * {website}/programabg.php?year=Y&month=M (windows-1251, one page per month,
 * one block per stage). Takes bare facts only: title, date, time, stage.
 */
class TbaAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'http://www.tba.art.bg';

    /** How many months ahead to read, including the current one. */
    public const MONTHS = 3;

    private const MONTHS_BG = [
        'януари' => 1, 'февруари' => 2, 'март' => 3, 'април' => 4, 'май' => 5, 'юни' => 6,
        'юли' => 7, 'август' => 8, 'септември' => 9, 'октомври' => 10, 'ноември' => 11, 'декември' => 12,
    ];

    /** Visiting companies are listed as "Заглавие - Гостува ДТ-Пловдив"; they are not this theatre's productions. */
    private const GUEST_PATTERN = '/(?<!\p{L})гостува(?!\p{L})/iu';

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/programabg.php?year={$month->year}&month={$month->month}";
            $response = Http::timeout(20)->retry(2, 1000)->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            yield from $this->parse(self::toUtf8($response->body(), $response->header('Content-Type')), $month->year, $month->month, $base);
        }
    }

    /**
     * Parse one month's programme page.
     *
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, int $year, int $month, string $base = self::DEFAULT_BASE_URL): array
    {
        $crawler = new Crawler($html);
        $blocks = $crawler->filter('div.text-content > div');

        if ($blocks->count() === 0) {
            throw new RuntimeException('Programme block (div.text-content) not found; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $stage = null;
        $performances = [];

        foreach ($blocks as $node) {
            $block = new Crawler($node);
            $class = (string) $node->getAttribute('class');

            if (str_contains($class, 'antetka-programa')) {
                $stage = self::cleanStage($block->text());

                continue;
            }

            if (! str_contains($class, 'program-row')) {
                continue;
            }

            $link = $block->filter('.program-right a');
            $rawTitle = $link->count() ? $link->text() : '';
            if ($rawTitle === '' || preg_match(self::GUEST_PATTERN, $rawTitle)) {
                continue;
            }

            [$day, $rowMonth] = self::parseDay($block->filter('.program-left .time')->text());
            $time = self::parseTime($block->filter('.program-right span')->text());

            // A page lists one month; a row in January on a December page belongs to next year.
            $rowYear = $rowMonth < $month ? $year + 1 : $year;

            $performances[] = new ScrapedPerformance(
                title: self::cleanTitle($rawTitle),
                startsAt: CarbonImmutable::create($rowYear, $rowMonth, $day, $time[0], $time[1], 0, $tz),
                stage: $stage,
                sourceUrl: $base.'/'.ltrim((string) $link->attr('href'), '/'),
            );
        }

        return $performances;
    }

    public static function cleanTitle(string $title): string
    {
        $title = Cyrillic::foldHomoglyphs($title);
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        return trim($title, " \t\n\r\0\x0B\"„“”");
    }

    private static function cleanStage(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        // "сцена-клуб "МаксиМ"" → "Сцена-клуб "МаксиМ""
        return mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
    }

    /** "02 Ноември (Понеделник)" → [2, 11] */
    private static function parseDay(string $text): array
    {
        if (! preg_match('/(\d{1,2})\s+(\p{L}+)/u', $text, $m) || ! isset(self::MONTHS_BG[mb_strtolower($m[2])])) {
            throw new RuntimeException("Unrecognised date: {$text}");
        }

        return [(int) $m[1], self::MONTHS_BG[mb_strtolower($m[2])]];
    }

    /** "19.30 ч." → [19, 30] */
    private static function parseTime(string $text): array
    {
        if (! preg_match('/(\d{1,2})[.:](\d{2})/', $text, $m)) {
            throw new RuntimeException("Unrecognised time: {$text}");
        }

        return [(int) $m[1], (int) $m[2]];
    }

    private static function toUtf8(string $body, ?string $contentType): string
    {
        $declared = preg_match('/charset=([\w-]+)/i', (string) $contentType, $m) ? strtolower($m[1]) : 'windows-1251';

        return $declared === 'utf-8' ? $body : mb_convert_encoding($body, 'UTF-8', 'Windows-1251');
    }
}
