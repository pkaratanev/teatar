<?php

namespace App\Support;

use App\Services\ProgrammeImporter;

/**
 * Guest shows written into the title: "Бариерата - Гостува ДТ-Пловдив",
 * "„Вишнева градина“ – Гостува ДКТ-Враца", "Лекция за "…" гостува ДТ-Пловдив".
 * Used by the theatres on the same platform (Българска армия, Младежки театър).
 */
final class GuestTitle
{
    private const PATTERN = '/^(.*?)\s*(?:[-–—]\s*)?(?<!\p{L})гостува(?!\p{L})\s+(.+)$/iu';

    /**
     * @return array{0: string, 1: string|null} [title, visiting company or null]
     */
    public static function split(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw);

        if (preg_match(self::PATTERN, $raw, $m) && trim($m[1]) !== '') {
            return [ProgrammeImporter::cleanTitle($m[1]), trim($m[2])];
        }

        return [ProgrammeImporter::cleanTitle($raw), null];
    }
}
