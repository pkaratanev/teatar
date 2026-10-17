<?php

namespace App\Adapters\Cast;

use App\Services\Credits;

/** "Режисьор: Иван Петров" / "Режисьор – Иван Петров" lines (split at <br>) → pairs, known credit labels only. */
final class LinePairs
{
    /** @return list<array{0: string, 1: string}> */
    public static function fromHtml(string $html, bool $firstBlockOnly = false): array
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
}
