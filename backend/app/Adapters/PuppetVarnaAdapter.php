<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use App\Support\GuestTitle;
use App\Support\TheatreHttp;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Държавен куклен театър – Варна (WordPress + The Events Calendar).
 * The plugin's public JSON feed lists every upcoming event in one request:
 * {website}/wp-json/tribe/events/v1/events?start_date=Y-m-d&per_page=100.
 * The site's robots.txt asks for a 300 s crawl delay, so this adapter makes exactly one
 * request per run. The feed also holds the English copies (/en/event/…), which are skipped.
 * Guests are written into the title: "Лазарица/гостува ДТ-Русе".
 */
class PuppetVarnaAdapter implements TheatreAdapter
{
    public const DEFAULT_BASE_URL = 'https://vnpuppet.com';

    public function fetch(Theatre $theatre): iterable
    {
        $base = rtrim($theatre->website ?: self::DEFAULT_BASE_URL, '/');
        $today = CarbonImmutable::now(config('app.theatre_timezone'))->format('Y-m-d');
        $url = "{$base}/wp-json/tribe/events/v1/events?start_date={$today}&per_page=100";

        $response = TheatreHttp::client()->acceptJson()->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("{$url} answered HTTP {$response->status()}");
        }

        return $this->parse($response->body());
    }

    /**
     * @return list<ScrapedPerformance>
     */
    public function parse(string $json): array
    {
        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['events']) || ! is_array($data['events'])) {
            throw new RuntimeException('No "events" list in the feed; the site may have changed.');
        }

        $tz = config('app.theatre_timezone');
        $performances = [];

        foreach ($data['events'] as $event) {
            $url = (string) ($event['url'] ?? '');
            if (str_contains($url, '/en/') || ! empty($event['all_day']) || empty($event['utc_start_date'])) {
                continue;
            }

            $raw = html_entity_decode((string) ($event['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // "Лазарица/гостува ДТ-Русе" → "Лазарица - гостува ДТ-Русе", the form GuestTitle reads.
            $raw = preg_replace('~\s*/\s*(?=гостува)~iu', ' - ', $raw) ?? $raw;
            [$title, $company] = GuestTitle::split($raw);
            if ($title === '') {
                continue;
            }

            $performances[] = new ScrapedPerformance(
                title: $title,
                startsAt: CarbonImmutable::createFromFormat('Y-m-d H:i:s', $event['utc_start_date'], 'UTC')->setTimezone($tz),
                sourceUrl: $url ?: null,
                visitingCompany: $company,
            );
        }

        return $performances;
    }
}
