<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** theatrevazrajdane.bg/portfolio/…: a details box of title/value items ("Автор", "Участват" …). */
class VazrajdaneCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/portfolio/');
    }

    public function read(string $html): array
    {
        $pairs = [];
        foreach ((new Crawler($html))->filter('.portfolio_page_details_item') as $item) {
            /** @var DOMElement $item */
            $row = new Crawler($item);
            $label = $row->filter('.portfolio_page_details_item_title');
            $value = $row->filter('.portfolio_page_details_item_value');
            if ($label->count() && $value->count()) {
                $pairs[] = [trim($label->text()), trim($value->text())];
            }
        }

        return $pairs;
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
