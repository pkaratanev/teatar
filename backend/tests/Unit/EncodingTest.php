<?php

use App\Support\Encoding;

it('leaves UTF-8 alone and converts windows-1251', function () {
    $utf8 = 'Програма за месец Ноември';

    expect(Encoding::toUtf8($utf8))->toBe($utf8)
        ->and(Encoding::toUtf8(mb_convert_encoding($utf8, 'Windows-1251', 'UTF-8')))->toBe($utf8);
});
