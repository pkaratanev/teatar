<?php

namespace App\Adapters\Cast;

use App\Services\Credits;

/** "Режисьор: Иван Петров" / "Режисьор – Иван Петров" lines (split at <br>) → pairs, known credit labels only. */
final class LinePairs
{
    /**
     * @param  bool  $prefixLabels  also "Режисьор Иван Петров" without a separator: the label is the
     *                              words before the first capitalised one after the first word (at most 3)
     * @return list<array{0: string, 1: string}>
     */
    public static function fromHtml(string $html, bool $firstBlockOnly = false, bool $prefixLabels = false): array
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $pairs = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $labelled = preg_match('/^([^:;–—]{2,40}?)\s*(?::|;|\s[–—-])\s*(.+)$/u', $line, $m)
                && count(preg_split('/\s+/u', trim($m[1])) ?: []) <= 4;
            if (! $labelled && $prefixLabels && ($prefix = self::prefixLabel($line)) !== null) {
                $m = [$line, ...$prefix];
                $labelled = true;
            }
            if (! $labelled) {
                // The credits block ended (a description or a cast table follows).
                if ($firstBlockOnly && $pairs !== []) {
                    break;
                }

                continue;
            }
            if (Credits::roles($m[1]) !== null) {
                $pairs[] = [trim($m[1]), trim($m[2])];
            }
        }

        return $pairs;
    }

    /** @return array{0: string, 1: string}|null "Постановка БОГДАН ПЕТКАНИН" → ["Постановка", "БОГДАН ПЕТКАНИН"] */
    public static function prefixLabel(string $line): ?array
    {
        $words = preg_split('/\s+/u', trim($line)) ?: [];
        $label = [array_shift($words)];
        while ($words !== [] && ! preg_match('/^\p{Lu}/u', $words[0]) && count($label) < 3) {
            $label[] = array_shift($words);
        }
        $label = implode(' ', $label);

        return $words !== [] && ! str_contains($label, ',') && Credits::roles($label) !== null
            ? [$label, implode(' ', $words)]
            : null;
    }
}
