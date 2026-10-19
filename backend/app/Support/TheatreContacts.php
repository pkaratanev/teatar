<?php

namespace App\Support;

use App\Models\Theatre;

/**
 * Address, box-office phone and ticket page of the launch theatres, from each theatre's own contacts page
 * (checked 2026-10-19). Coordinates are the building's point on OpenStreetMap. Used by the seeder and by the
 * migration that filled production; both only fill fields that are still empty, so an admin's edit stays.
 */
class TheatreContacts
{
    /** @var array<string, array{address: ?string, lat: float, lng: float, phone: ?string, box_office_url: ?string}> by adapter key, or by name for theatres without one */
    public const ALL = [
        'tba' => ['address' => 'ул. „Г. С. Раковски“ 98', 'lat' => 42.694766, 'lng' => 23.328891, 'phone' => '02 987 23 03', 'box_office_url' => null],
        'national' => ['address' => 'ул. „Дякон Игнатий“ 5', 'lat' => 42.694171, 'lng' => 23.326571, 'phone' => '02 811 92 27', 'box_office_url' => 'https://www.nationaltheatre.bg/bg/programa'],
        'salzaismyah' => ['address' => 'ул. „Г. С. Раковски“ 127', 'lat' => 42.693721, 'lng' => 23.328317, 'phone' => '02 987 58 95', 'box_office_url' => null],
        'mlt' => ['address' => 'бул. „Княз Александър Дондуков“ 8', 'lat' => 42.697643, 'lng' => 23.326931, 'phone' => '02 939 40 59', 'box_office_url' => 'http://tickets.mlt.bg/'],
        'theatre199' => ['address' => 'ул. „Славянска“ 8', 'lat' => 42.693519, 'lng' => 23.328141, 'phone' => '0885 199 199', 'box_office_url' => 'https://theatre199.org/bg/tickets'],
        // Sfumato's site forbids robots, so no address or phone from it; the point is from OpenStreetMap.
        'Театрална работилница „Сфумато“' => ['address' => null, 'lat' => 42.697929, 'lng' => 23.346113, 'phone' => null, 'box_office_url' => null],
        'satirata' => ['address' => 'ул. „Стефан Караджа“ 26', 'lat' => 42.691616, 'lng' => 23.326319, 'phone' => '0882 565 653', 'box_office_url' => 'https://satirata.bg/tickets'],
        'zadkanala' => ['address' => 'бул. „Мадрид“ 1', 'lat' => 42.69638, 'lng' => 23.347405, 'phone' => '02 944 24 48', 'box_office_url' => null],
        'sofia' => ['address' => 'бул. „Янко Сакъзов“ 23А', 'lat' => 42.69725, 'lng' => 23.345385, 'phone' => '0878 829 102', 'box_office_url' => 'https://sofiatheatre.eu/tickets'],
        'vazrajdane' => ['address' => 'пл. „Славейков“ 4, в Столична библиотека', 'lat' => 42.691723, 'lng' => 23.324246, 'phone' => '02 987 24 31', 'box_office_url' => null],
        'theatro' => ['address' => 'ул. „Върбица“ 12', 'lat' => 42.6954, 'lng' => 23.345949, 'phone' => '02 494 99 80', 'box_office_url' => 'https://www.theatro.bg/tickets/'],
        'dramavarna' => ['address' => 'пл. „Независимост“ 1', 'lat' => 43.203554, 'lng' => 27.912241, 'phone' => '052 669 680', 'box_office_url' => 'https://dramavarna.com/index.php/bg/program-and-tickets'],
        'vnpuppet' => ['address' => 'ул. „Драгоман“ 4', 'lat' => 43.203655, 'lng' => 27.913956, 'phone' => '052 607 844', 'box_office_url' => 'https://vnpuppet.com/%D0%BA%D1%83%D0%BF%D0%B8-%D0%BE%D0%BD%D0%BB%D0%B0%D0%B9%D0%BD/'],
    ];

    /** Fills the theatre's empty contact fields; returns whether anything changed. */
    public static function fill(Theatre $theatre): bool
    {
        $known = self::ALL[$theatre->adapter ?? ''] ?? self::ALL[$theatre->name] ?? null;
        if ($known === null) {
            return false;
        }

        foreach ($known as $field => $value) {
            if ($value !== null && blank($theatre->getAttribute($field))) {
                $theatre->setAttribute($field, $value);
            }
        }

        return $theatre->isDirty() && $theatre->save();
    }
}
