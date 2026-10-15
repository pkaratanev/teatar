<?php

namespace App\Support;

/**
 * Theatre sites sometimes type a Latin letter inside a Cyrillic word
 * ("Бaлдахинът" with a Latin a). It looks identical but breaks matching.
 */
final class Cyrillic
{
    private const LATIN_TO_CYRILLIC = [
        'a' => 'а', 'c' => 'с', 'e' => 'е', 'o' => 'о', 'p' => 'р', 'x' => 'х', 'y' => 'у',
        'A' => 'А', 'B' => 'В', 'C' => 'С', 'E' => 'Е', 'H' => 'Н', 'K' => 'К', 'M' => 'М',
        'O' => 'О', 'P' => 'Р', 'T' => 'Т', 'X' => 'Х', 'Y' => 'У',
    ];

    /** Replace Latin look-alikes, but only inside words that already contain Cyrillic. */
    public static function foldHomoglyphs(string $text): string
    {
        return preg_replace_callback(
            '/[\p{L}]+/u',
            fn (array $m) => preg_match('/\p{Cyrillic}/u', $m[0]) ? strtr($m[0], self::LATIN_TO_CYRILLIC) : $m[0],
            $text,
        ) ?? $text;
    }
}
