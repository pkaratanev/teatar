<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Services\ProgrammeImporter;
use App\Support\BulgarianDate;
use App\Support\Encoding;
use App\Support\GuestTitle;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
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


    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $month = CarbonImmutable::now(config('app.theatre_timezone'))->startOfMonth();

        for ($i = 0; $i < self::MONTHS; $i++, $month = $month->addMonth()) {
            $url = "{$base}/programabg.php?year={$month->year}&month={$month->month}";
            $response = TheatreHttp::client()->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("{$url} answered HTTP {$response->status()}");
            }

            yield from $this->parse(Encoding::toUtf8($response->body()), $month->year, $month->month, $base);
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
            $class = (string) $block->attr('class');

            if (str_contains($class, 'antetka-programa')) {
                $stage = self::cleanStage($block->text());

                continue;
            }

            if (! str_contains($class, 'program-row')) {
                continue;
            }

            $link = $block->filter('.program-right a');
            $rawTitle = $link->count() ? $link->text() : '';
            if ($rawTitle === '') {
                continue;
            }

            [$title, $visitingCompany] = self::splitGuest($rawTitle);

            [$day, $rowMonth] = self::parseDay($block->filter('.program-left .time')->text());
            $time = BulgarianDate::time($block->filter('.program-right span')->text());

            // A page lists one month; a row in January on a December page belongs to next year.
            $rowYear = $rowMonth < $month ? $year + 1 : $year;

            $performances[] = new ScrapedPerformance(
                title: $title,
                startsAt: CarbonImmutable::create($rowYear, $rowMonth, $day, $time[0], $time[1], 0, $tz),
                stage: $stage,
                sourceUrl: $base.'/'.ltrim((string) $link->attr('href'), '/'),
                visitingCompany: $visitingCompany,
            );
        }

        return $performances;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    public static function splitGuest(string $raw): array
    {
        return GuestTitle::split($raw);
    }

    public static function cleanTitle(string $title): string
    {
        return ProgrammeImporter::cleanTitle($title);
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
        if (! preg_match('/(\d{1,2})\s+(\p{L}+)/u', $text, $m)) {
            throw new RuntimeException("Unrecognised date: {$text}");
        }

        return [(int) $m[1], BulgarianDate::month($m[2])];
    }
}
