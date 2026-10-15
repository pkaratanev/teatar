<?php

namespace App\Adapters;

use App\Adapters\Contracts\TheatreAdapter;
use App\Models\Theatre;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * For theatres that send a monthly spreadsheet instead of publishing a
 * machine-readable programme. Export the sheet as CSV (UTF-8) and drop it at
 * storage/app/private/programmes/{theatre-slug}.csv with this header row:
 *
 *   date,time,title,stage,ticket_url,price_from,price_to
 *   2026-11-03,19:00,Гняв,Голяма сцена,https://…,10,15
 */
class CsvProgrammeAdapter implements TheatreAdapter
{
    public const REQUIRED_COLUMNS = ['date', 'time', 'title'];

    public function fetch(Theatre $theatre): iterable
    {
        $path = "programmes/{$theatre->slug}.csv";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            throw new RuntimeException("No programme file at storage/app/private/{$path}");
        }

        $handle = fopen($disk->path($path), 'r');
        $header = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), fgetcsv($handle, escape: '') ?: []);

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            fclose($handle);
            throw new RuntimeException('Programme CSV is missing columns: '.implode(', ', $missing));
        }

        try {
            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if ($row === [null] || count($row) !== count($header)) {
                    continue; // blank or malformed line
                }

                $r = array_combine($header, array_map('trim', $row));

                yield new ScrapedPerformance(
                    title: $r['title'],
                    startsAt: CarbonImmutable::createFromFormat('Y-m-d H:i', "{$r['date']} {$r['time']}", config('app.theatre_timezone')),
                    stage: ($r['stage'] ?? '') ?: null,
                    ticketUrl: ($r['ticket_url'] ?? '') ?: null,
                    priceFrom: is_numeric($r['price_from'] ?? null) ? (float) $r['price_from'] : null,
                    priceTo: is_numeric($r['price_to'] ?? null) ? (float) $r['price_to'] : null,
                );
            }
        } finally {
            fclose($handle);
        }
    }
}
