<?php

use App\Support\Cyrillic;

it('folds Latin look-alikes only inside Cyrillic words', function (string $in, string $out) {
    expect(Cyrillic::foldHomoglyphs($in))->toBe($out);
})->with([
    ['Бaлдахинът', 'Балдахинът'],
    ['Хамлет', 'Хамлет'],
    ['Cyrano', 'Cyrano'],              // pure Latin word is left alone
    ['МaксиМ club', 'МаксиМ club'],
]);
