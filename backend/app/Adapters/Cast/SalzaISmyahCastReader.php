<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * salzaismyah.bg/site/play/…: paragraphs "Label <strong>Names</strong>" ("Постановка БОГДАН ПЕТКАНИН"),
 * with "от …" for the author. Guest shows from other theatres use the same page.
 */
class SalzaISmyahCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/site/play/');
    }

    public function read(string $html): array
    {
        $pairs = [];
        foreach ((new Crawler($html))->filter('.mr-md-3.mr-lg-3 p') as $p) {
            /** @var DOMElement $p */
            $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $p->textContent)) ?? '');
            if (preg_match('/^от\s+(\p{Lu}.{2,80})$/u', $text, $m)) {
                $pairs[] = ['Автор', $m[1]];

                continue;
            }
            foreach (LinePairs::fromHtml((new Crawler($p))->html(), prefixLabels: true) as $pair) {
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
