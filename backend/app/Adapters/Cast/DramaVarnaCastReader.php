<?php

namespace App\Adapters\Cast;

use Symfony\Component\DomCrawler\Crawler;

/**
 * dramavarna.com event pages: the team as one line "Режисьор: Име, Композитор: Име, Фотография: Име, Име",
 * the cast as .cast .member .name entries (characters are commented out in the page).
 */
class DramaVarnaCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, 'program-and-tickets?event=');
    }

    public function read(string $html): array
    {
        $crawler = new Crawler($html);
        $pairs = [];

        $team = $crawler->filter('.right > .members');
        if ($team->count()) {
            $text = trim(preg_replace('/\s+/u', ' ', $team->first()->text()) ?? '');
            // Split before every "Label:"; a label has no comma and at most a few words.
            $parts = preg_split('/(?:^|,\s*)(?=[^,:]{2,40}:)/u', $text) ?: [];
            foreach ($parts as $part) {
                if (preg_match('/^([^,:]{2,40}):\s*(.+)$/u', trim($part), $m)) {
                    $pairs[] = [trim($m[1]), trim($m[2])];
                }
            }
        }

        $actors = $crawler->filter('.cast .member .name')->each(
            fn (Crawler $name) => trim(preg_replace('/(,|\sи)\s*$/u', '', trim($name->text())) ?? ''),
        );
        $actors = array_values(array_filter($actors));
        if ($actors !== []) {
            $pairs[] = ['Актьори', implode(', ', $actors)];
        }

        return $pairs;
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
