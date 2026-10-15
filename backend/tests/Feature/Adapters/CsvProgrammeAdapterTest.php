<?php

use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Performance;
use App\Models\Theatre;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('imports a theatre programme from a CSV file and logs the run', function () {
    $theatre = Theatre::factory()->create(['slug' => 'test-teatar', 'adapter' => 'csv']);

    Storage::disk('local')->put('programmes/test-teatar.csv', <<<'CSV'
        date,time,title,stage,ticket_url,price_from,price_to
        2026-11-03,19:00,Гняв,Голяма сцена,https://example.test/t/1,10,15
        2026-11-04,19:30,Албион,,,,
        CSV);

    SyncTheatreProgramme::dispatchSync($theatre);

    $run = AdapterRun::sole();
    expect($run->status)->toBe('succeeded')
        ->and($run->performances_found)->toBe(2)
        ->and(Performance::count())->toBe(2)
        ->and((float) Performance::whereNotNull('ticket_url')->sole()->price_from)->toBe(10.0);
});

it('records a failed run instead of throwing when the file is missing', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);

    SyncTheatreProgramme::dispatchSync($theatre);

    expect(AdapterRun::sole())
        ->status->toBe('failed')
        ->error->toContain('No programme file');
});
