<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Dates as Bulgarian theatre sites print them: "02 Ноември (Понеделник)",
 * "03 Ное, вторник", "1 Ноем. 2026", "17 Oct, Sat" — often without a year.
 */
final class BulgarianDate
{
    /** Month stems; a word matches when it starts with one ("ное", "ноем.", "ноември"). */
    private const MONTHS = [
        'яну' => 1, 'фев' => 2, 'мар' => 3, 'апр' => 4, 'май' => 5, 'юни' => 6,
        'юли' => 7, 'авг' => 8, 'сеп' => 9, 'окт' => 10, 'ное' => 11, 'дек' => 12,
        // English abbreviations appear on some sites (sofiatheatre.eu).
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /** ISO weekday (1 = Monday) by stem. */
    private const WEEKDAYS = [
        'пон' => 1, 'вт' => 2, 'ср' => 3, 'чет' => 4, 'пет' => 5, 'съб' => 6, 'нед' => 7,
        'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7,
    ];

    public static function month(string $word): int
    {
        $word = mb_strtolower(trim($word, " .,\t\n"));

        // "юни" and "юли" share the stem "ю", so check them whole first.
        foreach (['юни' => 6, 'юли' => 7, 'jun' => 6, 'jul' => 7] as $stem => $n) {
            if (str_starts_with($word, $stem)) {
                return $n;
            }
        }
        foreach (self::MONTHS as $stem => $n) {
            if (str_starts_with($word, $stem)) {
                return $n;
            }
        }

        throw new RuntimeException("Unrecognised month: {$word}");
    }

    /** First weekday word found in the text, as ISO weekday, or null. */
    public static function weekday(string $text): ?int
    {
        foreach (preg_split('/[^\p{L}]+/u', mb_strtolower($text)) ?: [] as $word) {
            foreach (self::WEEKDAYS as $stem => $n) {
                if ($word !== '' && str_starts_with($word, $stem)) {
                    return $n;
                }
            }
        }

        return null;
    }

    /**
     * Pick the year for a day/month printed without one. Programmes list the
     * near future, so take the nearest year that falls between 60 days ago
     * and ~11 months ahead; when a weekday is printed, it must match.
     */
    public static function inferYear(int $day, int $month, ?int $isoWeekday, CarbonImmutable $today): int
    {
        $today = $today->startOfDay();
        $candidates = [$today->year, $today->year + 1, $today->year - 1];

        foreach ([true, false] as $useWeekday) {
            foreach ($candidates as $year) {
                if (! checkdate($month, $day, $year)) {
                    continue;
                }
                $date = CarbonImmutable::create($year, $month, $day, 0, 0, 0, $today->timezone);
                if ($date->lt($today->subDays(60)) || $date->gt($today->addDays(330))) {
                    continue;
                }
                if ($useWeekday && $isoWeekday !== null && $date->dayOfWeekIso !== $isoWeekday) {
                    continue;
                }

                return $year;
            }
        }

        return $today->year;
    }

    /** "19.30 ч." / "19:00" → [19, 30] */
    public static function time(string $text): array
    {
        if (! preg_match('/(\d{1,2})[.:](\d{2})/', $text, $m)) {
            throw new RuntimeException("Unrecognised time: {$text}");
        }

        return [(int) $m[1], (int) $m[2]];
    }
}
