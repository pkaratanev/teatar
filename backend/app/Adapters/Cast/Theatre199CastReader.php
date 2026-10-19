<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/** theatre199.org/bg/plays/p/…: a list of "<strong>label</strong> linked names". */
class Theatre199CastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/plays/p/');
    }

    public function read(string $html): array
    {
        $pairs = [];
        foreach ((new Crawler($html))->filter('.article-info li') as $li) {
            /** @var DOMElement $li */
            $row = new Crawler($li);
            $label = $row->filter('strong');
            $names = $row->filter('a')->each(fn (Crawler $a) => trim($a->text()));
            if ($label->count() && $names !== []) {
                $pairs[] = [trim($label->text()), implode(', ', $names)];
            }
        }

        return $pairs;
    }

    public function delaySeconds(): int
    {
        return 2;
    }
}
