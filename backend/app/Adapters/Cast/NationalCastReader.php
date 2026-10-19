<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** nationaltheatre.bg/bg/predstavlenie/…: a "Постановъчен екип" section (label + linked names) and an actors carousel. */
class NationalCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/predstavlenie/');
    }

    public function read(string $html): array
    {
        $crawler = new Crawler($html);
        $pairs = [];

        foreach ($crawler->filter('#production-team .item') as $item) {
            /** @var DOMElement $item */
            $row = new Crawler($item);
            $label = $row->filter('h5');
            $names = $row->filter('a.name')->each(fn (Crawler $a) => trim($a->text()));
            if ($label->count() && $names !== []) {
                $pairs[] = [trim($label->text()), implode(', ', $names)];
            }
        }

        $actors = $crawler->filter('#actors .item img')->each(fn (Crawler $img) => trim((string) $img->attr('alt')));
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
