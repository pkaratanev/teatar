<?php

use App\Support\Encoding;
use Symfony\Component\DomCrawler\Crawler;

it('leaves UTF-8 alone and converts windows-1251', function () {
    $utf8 = 'Програма за месец Ноември';

    expect(Encoding::toUtf8($utf8))->toBe($utf8)
        ->and(Encoding::toUtf8(mb_convert_encoding($utf8, 'Windows-1251', 'UTF-8')))->toBe($utf8);
});

it('fixes a windows-1251 meta tag on a UTF-8 page so the parser does not convert twice', function () {
    // tba.art.bg answered a datacenter client like this.
    $page = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"></head>'
        .'<body><div class="time">02 Ноември</div></body></html>';

    expect((new Crawler(Encoding::toUtf8($page)))->filter('.time')->text())->toBe('02 Ноември');
});

it('handles a windows-1251 page that says so', function () {
    $page = mb_convert_encoding('<html><head><meta charset="windows-1251"></head><body><p>Гняв</p></body></html>', 'Windows-1251', 'UTF-8');

    expect((new Crawler(Encoding::toUtf8($page)))->filter('p')->text())->toBe('Гняв');
});
