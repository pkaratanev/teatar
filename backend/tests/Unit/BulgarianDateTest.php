<?php

use App\Support\BulgarianDate;
use Carbon\CarbonImmutable;

it('reads full, abbreviated and English month names', function (string $word, int $n) {
    expect(BulgarianDate::month($word))->toBe($n);
})->with([
    ['Ноември', 11], ['ное', 11], ['Ноем.', 11], ['Дек', 12], ['декември', 12],
    ['Юни', 6], ['юли', 7], ['Май', 5], ['Oct', 10], ['януари', 1],
]);

it('finds the weekday in a date line', function (string $text, ?int $n) {
    expect(BulgarianDate::weekday($text))->toBe($n);
})->with([
    ['02 Ноември (Понеделник)', 1],
    ['03 Ное, вторник, 19:00 ч.', 2],
    ['17 Oct, Sat', 6],
    ['01 декември', null],
]);

it('infers the year for dates printed without one', function (int $day, int $month, ?int $weekday, string $today, int $year) {
    expect(BulgarianDate::inferYear($day, $month, $weekday, CarbonImmutable::parse($today, 'Europe/Sofia')))->toBe($year);
})->with([
    'later this year' => [3, 11, null, '2026-10-15', 2026],
    'January seen in October' => [5, 1, null, '2026-10-15', 2027],
    'a few days ago' => [10, 10, null, '2026-10-15', 2026],
    'December seen in January' => [28, 12, null, '2027-01-05', 2026],
    'weekday decides' => [18, 11, 3, '2026-10-15', 2026],      // Wed 18 Nov 2026
    'stale weekday falls back' => [18, 11, 2, '2026-10-15', 2026], // "вторник" was 2025; still pick the near year
]);

it('reads times with a dot or a colon', function () {
    expect(BulgarianDate::time('19.30 ч.'))->toBe([19, 30])
        ->and(BulgarianDate::time('11:00 ч. и 12:30 ч.'))->toBe([11, 0]);
});
