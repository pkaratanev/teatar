<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** sofiatheatre.eu/repertoires/…: "Творчески екип" boxes (label, name) and an "Участват" list of actor links in capitals. */
class SofiaTheatreCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/repertoires/');
    }

    public function read(string $html): array
    {
        $crawler = new Crawler($html);
        $pairs = [];

        foreach ($crawler->filter('.repertoire-team .row > div') as $box) {
            /** @var DOMElement $box */
            $row = new Crawler($box);
            $label = $row->filter('p');
            $name = $row->filter('span');
            if ($label->count() && $name->count()) {
                $pairs[] = [trim($label->text()), trim($name->text())];
            }
        }

        $actors = $crawler->filterXPath('//h2[normalize-space(.)="Участват"]/parent::*/following-sibling::*[1]//a')
            ->each(fn (Crawler $a) => trim($a->text(), " ,\t\n\r"));
        $actors = array_values(array_unique(array_filter($actors)));
        if ($actors !== []) {
            $pairs[] = ['Актьори', implode(', ', $actors)];
        }

        // The page repeats the team block (two layouts).
        return array_values(array_unique($pairs, SORT_REGULAR));
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
