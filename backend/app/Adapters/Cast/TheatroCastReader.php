<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * theatro.bg/portfolio_page/…: credits come either as sidebar fields (h6 label, p value) or as
 * "Label: names" / "Label – names" lines in the text. Only known credit labels count, so the
 * description and the box-office lines are ignored.
 */
class TheatroCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/portfolio_page/');
    }

    public function read(string $html): array
    {
        $crawler = new Crawler($html);
        $pairs = [];

        foreach ($crawler->filter('.portfolio_custom_field') as $field) {
            /** @var DOMElement $field */
            $row = new Crawler($field);
            $label = $row->filter('h6');
            $value = $row->filter('p');
            if ($label->count() && $value->count()) {
                $pairs[] = [trim($label->text()), trim($value->text())];
            }
        }

        foreach ($crawler->filter('p') as $paragraph) {
            foreach (LinePairs::fromHtml((new Crawler($paragraph))->html()) as $pair) {
                $pairs[] = $pair;
            }
        }

        return array_values(array_unique($pairs, SORT_REGULAR));
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
