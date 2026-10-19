<?php

namespace App\Adapters\Cast;

use Symfony\Component\DomCrawler\Crawler;

/** tba.art.bg (Българска армия), pages "{title}___{id}": "Label: names" lines in the first block of .text-content. */
class TbaCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '___');
    }

    public function read(string $html): array
    {
        $block = (new Crawler($html))->filter('.text-content');
        if (! $block->count()) {
            return [];
        }

        return LinePairs::fromHtml($block->first()->html(), firstBlockOnly: true);
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
