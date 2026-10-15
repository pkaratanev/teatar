<?php

use App\Services\ProgrammeImporter;

it('normalises titles for matching', function (string $in, string $out) {
    expect(ProgrammeImporter::normaliseTitle($in))->toBe($out);
})->with([
    ['"ГНЯВ"', 'гняв'],
    ['„Мечтата на идиотката“', 'мечтата на идиотката'],
    ['  Ало,   ало! ', 'ало, ало!'],
]);
