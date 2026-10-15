<?php

use App\Services\ProgrammeImporter;

it('normalises titles for matching', function (string $in, string $out) {
    expect(ProgrammeImporter::normaliseTitle($in))->toBe($out);
})->with([
    ['"ГНЯВ"', 'гняв'],
    ['„Мечтата на идиотката“', 'мечтата на идиотката'],
    ['  Ало,   ало! ', 'ало, ало!'],
]);

it('removes quotes only when they wrap the whole title', function (string $in, string $out) {
    expect(ProgrammeImporter::cleanTitle($in))->toBe($out);
})->with([
    ['"Гняв"', 'Гняв'],
    ['„Мечтата на идиотката“', 'Мечтата на идиотката'],
    ['"Кафе с претенция" ', 'Кафе с претенция'],
    ['Лекция №3 за "СИЛАТА НА СЛОВОТО"', 'Лекция №3 за "СИЛАТА НА СЛОВОТО"'],
    ['"Бaлдахинът"', 'Балдахинът'],
]);
