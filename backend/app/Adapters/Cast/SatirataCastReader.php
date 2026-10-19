<?php

namespace App\Adapters\Cast;

use App\Services\Credits;
use Symfony\Component\DomCrawler\Crawler;

/**
 * satirata.bg/show/…: "Постановъчен екип" as lines "режисьор Анастасия Събева", "по Марк Твен", and the
 * cast as "Персонаж – ИМЕ ФАМИЛИЯ" lines (several characters may share one actor; "Име/Име" alternate).
 */
class SatirataCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/show/');
    }

    public function read(string $html): array
    {
        $block = (new Crawler($html))->filter('.show-details .col-6');
        if (! $block->count()) {
            return [];
        }

        $html = preg_replace('/<br\s*\/?>|<\/p>|<\/h\d>/i', "\n", $block->first()->html()) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $pairs = [];
        foreach (preg_split('/\R/u', str_replace("\u{00A0}", ' ', $text)) ?: [] as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
            if (preg_match('/^(.{2,80}?)\s+[–—-]\s+(\p{Lu}.+)$/u', $line, $m)) {
                [$left, $right] = [trim($m[1]), trim($m[2])];
                if (Credits::roles($left) !== null) {
                    $pairs[] = [$left, $right]; // "драматург на постановката – Богдана Костуркова"
                } elseif (! preg_match('/помощник|асистент/iu', $left)) {
                    // "Учител, Адвокат – ТЕОДОР ЕЛМАЗОВ (студент …)", "Флорънс – Петър Калчев/Николай Върбанов"
                    foreach (explode('/', $right) as $actor) {
                        $actor = trim(preg_replace('/\(.*\)/u', '', $actor) ?? $actor);
                        if ($actor !== '') {
                            $pairs[] = ['Актьори', "{$actor} ({$left})"];
                        }
                    }
                }
            } elseif (preg_match('/^([^:]{2,40}):\s*(.+)$/u', $line, $m) && Credits::roles($m[1]) !== null) {
                $pairs[] = [trim($m[1]), trim($m[2])];
            } elseif (preg_match('/^(по|от)\s+(\p{Lu}.{2,80})$/u', $line, $m)) {
                $pairs[] = ['Автор', $m[2]];
            } elseif (($pair = LinePairs::prefixLabel($line)) !== null) {
                $pairs[] = $pair;
            }
        }

        return $pairs;
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
