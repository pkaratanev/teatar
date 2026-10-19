<?php

namespace App\Adapters\Cast;

/**
 * Reads the team and cast from one theatre's production page, as label/value pairs
 * ("Режисьор" → "Иван Петров"). App\Services\Credits turns the pairs into people.
 */
interface CastReader
{
    /** Only production pages: a programme page lists many shows and must never be read as one. */
    public function accepts(string $url): bool;

    /** @return list<array{0: string, 1: string}> */
    public function read(string $html): array;

    /** Pause between two pages of this site (its robots.txt Crawl-delay, or a polite default). */
    public function delaySeconds(): int;
}
