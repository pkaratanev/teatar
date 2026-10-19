<?php

namespace App\Adapters\Cast;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * zadkanala.bg/spektakli/…: Drupal fields, each with a label ("Режисьор:", "Участват") and
 * one item per person; the author is an unlabelled "от …" field.
 */
class ZadKanalaCastReader implements CastReader
{
    public function accepts(string $url): bool
    {
        return str_contains($url, '/spektakli/');
    }

    public function read(string $html): array
    {
        $crawler = new Crawler($html);
        $pairs = [];

        $author = $crawler->filter('.field-name-field-author .field-item');
        if ($author->count()) {
            $pairs[] = ['Автор', trim($author->first()->text())];
        }

        foreach ($crawler->filter('.field') as $field) {
            /** @var DOMElement $field */
            $row = new Crawler($field);
            $label = $row->filter('.field-label');
            if (! $label->count()) {
                continue;
            }
            $values = $row->filter('.field-item')->each(fn (Crawler $item) => trim($item->text()));
            $values = array_values(array_filter($values, fn (string $v) => $v !== ''));
            if ($values !== []) {
                $pairs[] = [trim($label->first()->text()), implode(', ', $values)];
            }
        }

        return $pairs;
    }

    /** robots.txt: Crawl-delay: 10 */
    public function delaySeconds(): int
    {
        return 10;
    }
}
