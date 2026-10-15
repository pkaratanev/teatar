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
 * Théatro — one long WordPress page at {website}/program/. Every performance
 * is a page-builder row with four columns: date/time/hall, image, text
 * (title, "гостува …", credits), ticket buttons. Dates have no year.
 */
class TheatroAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://www.theatro.bg';

    /** Canonical spelling of halls; the site mixes "Голяма зала" and "Голяма Зала". */
    private const STAGES = ['голяма зала' => 'Голяма зала', 'сцена антракт' => 'Сцена Антракт'];

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $url = "{$base}/program/";
        $response = Http::timeout(20)->retry(2, 1000)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("{$url} answered HTTP {$response->status()}");
        }

        return $this->parse($response->body(), CarbonImmutable::now(config('app.theatre_timezone')), $url);
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $html, CarbonImmutable $today, string $pageUrl = self::DEFAULT_BASE_URL.'/program/'): array
    {
        $rows = (new Crawler($html))->filter('div.vc_row');

        if ($rows->count() === 0) {
            throw new RuntimeException('No page-builder rows (div.vc_row) found; the site layout may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($rows as $rowNode) {
            $row = new Crawler($rowNode);
            $columns = $row->filter('div.wpb_column');

            // Hidden promo rows: every column is hidden at every screen size.
            if ($columns->count() < 3 || $columns->reduce(fn (Crawler $c) => ! str_contains((string) $c->attr('class'), 'vc_hidden-xs'))->count() === 0) {
                continue;
            }

            $when = $columns->eq(0)->filter('p')->each(fn (Crawler $p) => self::squish($p->text()));
            if (count($when) < 2 || ! preg_match('/^(\d{1,2})\s+(\p{L}+)/u', $when[0], $d)) {
                continue; // headings and other non-performance rows
            }

            $text = $columns->eq(2);
            $lines = array_values(array_filter($text->filter('p')->each(fn (Crawler $p) => self::squish($p->text()))));
            if ($lines === [] || str_contains(mb_strtolower(implode(' ', $lines)), 'се отменя')) {
                continue; // cancellation notice
            }

            [$title, $authorFromTitle] = self::splitTitle($lines[0]);
            $visiting = self::firstMatch($lines, '/^гостува\s+(.+)$/iu');
            $author = self::firstMatch($lines, '/^автор:\s*(.+)$/iu') ?? $authorFromTitle;

            $day = (int) $d[1];
            $month = BulgarianDate::month($d[2]);
            $year = BulgarianDate::inferYear($day, $month, BulgarianDate::weekday($when[0]), $today);
            [$hour, $minute] = BulgarianDate::time($when[1]);

            $more = $text->filter('a')->reduce(fn (Crawler $a) => str_contains((string) $a->attr('href'), '/portfolio_page/'));
            $ticket = $columns->count() > 3 ? $columns->eq(3)->filter('a') : null;

            $performances[] = new ScrapedPerformance(
                title: $title,
                startsAt: CarbonImmutable::create($year, $month, $day, $hour, $minute, 0, $tz),
                stage: isset($when[2]) ? self::stage($when[2]) : null,
                ticketUrl: $ticket && $ticket->count() ? (string) $ticket->first()->attr('href') : null,
                sourceUrl: $more->count() ? (string) $more->first()->attr('href') : $pageUrl,
                visitingCompany: $visiting,
                author: $author,
            );
        }

        return $performances;
    }

    /**
     * "„Трудни за обичане“ по Леополд Герш" → ["Трудни за обичане", "по Леополд Герш"];
     * "Théatro AURA" → ["Théatro AURA", null]. Accepts the odd closing quotes the site uses (″).
     *
     * @return array{0: string, 1: string|null}
     */
    public static function splitTitle(string $line): array
    {
        if (preg_match('/^[„"“«]\s*(.+?)\s*[“”"″»]\s*(.*)$/u', $line, $m)) {
            $rest = trim($m[2], " –-\t");
            $author = preg_match('/^(по|от)\s+\S/u', $rest) ? $rest : null;

            return [ProgrammeImporter::cleanTitle($m[1]), $author];
        }

        return [ProgrammeImporter::cleanTitle($line), null];
    }

    private static function stage(string $name): string
    {
        return self::STAGES[mb_strtolower($name)] ?? mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
    }

    /** @param  list<string>  $lines */
    private static function firstMatch(array $lines, string $pattern): ?string
    {
        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    private static function squish(string $text): string
    {
        // &nbsp; arrives as U+00A0.
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? $text);
    }
}
