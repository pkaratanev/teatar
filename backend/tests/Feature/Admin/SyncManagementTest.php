<?php

use App\Filament\Resources\AdapterRuns\Pages\ListAdapterRuns;
use App\Jobs\SyncTheatreProgramme;
use App\Models\AdapterRun;
use App\Models\Theatre;
use App\Models\User;
use App\Services\ProgrammeSync;
use App\Services\SyncAlerts;
use App\Support\SyncSchedule;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function run(Theatre $theatre, string $status, array $extra = []): AdapterRun
{
    return AdapterRun::create([
        'theatre_id' => $theatre->id,
        'adapter' => (string) $theatre->adapter,
        'status' => $status,
        'started_at' => now(),
        'performances_found' => 10,
        ...$extra,
    ]);
}

beforeEach(function () {
    Queue::fake();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// "Обнови всички"

it('queues every active theatre with an adapter, once', function () {
    $a = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'А']);
    $b = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Б']);
    Theatre::factory()->create(['adapter' => 'csv', 'is_active' => false]);
    Theatre::factory()->create(['adapter' => null]);

    $result = app(ProgrammeSync::class)->queueAll();

    expect($result)->toBe(['queued' => ['А', 'Б'], 'skipped' => []]);
    Queue::assertPushed(SyncTheatreProgramme::class, 2);
    expect(AdapterRun::where('status', 'queued')->pluck('theatre_id')->sort()->values()->all())->toBe([$a->id, $b->id]);

    // A second click while those are queued does nothing.
    expect(app(ProgrammeSync::class)->queueAll())->toBe(['queued' => [], 'skipped' => ['А', 'Б']]);
    Queue::assertPushed(SyncTheatreProgramme::class, 2);
});

it('skips a theatre that is already running', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);
    run($theatre, 'running');

    expect(app(ProgrammeSync::class)->queue($theatre))->toBeNull();
    Queue::assertNothingPushed();
});

it('has a sync-all button on the sync log', function () {
    Theatre::factory()->count(2)->create(['adapter' => 'csv']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListAdapterRuns::class)
        ->callAction('syncAll')
        ->assertNotified('Пуснати: 2 театъра');

    Queue::assertPushed(SyncTheatreProgramme::class, 2);
});

it('takes over the queued run when the job starts', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'nope']);
    $run = run($theatre, 'queued');

    (new SyncTheatreProgramme($theatre, $run->id))->handle(app(\App\Services\ProgrammeImporter::class));

    expect(AdapterRun::count())->toBe(1)
        ->and($run->fresh()->status)->toBe('failed')
        ->and($run->fresh()->error)->toContain('Unknown adapter');
});

// Stuck runs

it('marks runs that never finished as failed', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);
    $stuck = run($theatre, 'running', ['started_at' => now()->subHour()]);
    $fresh = run($theatre, 'running', ['started_at' => now()->subMinutes(5)]);
    $neverStarted = run($theatre, 'queued', ['started_at' => now()->subHours(3)]);

    expect(AdapterRun::markStale())->toBe(2)
        ->and($stuck->fresh()->status)->toBe('failed')
        ->and($stuck->fresh()->error)->toContain('Прекъснато')
        ->and($stuck->fresh()->finished_at)->not->toBeNull()
        ->and($fresh->fresh()->status)->toBe('running')
        ->and($neverStarted->fresh()->error)->toContain('queue worker');
});

it('closes the run when the worker gives up on the job', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);
    $run = run($theatre, 'running');

    (new SyncTheatreProgramme($theatre, $run->id))->failed(new RuntimeException('timed out'));

    expect($run->fresh())->status->toBe('failed')->error->toBe('Прекъснато: timed out');
});

it('lets a stuck theatre be synced again', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv']);
    run($theatre, 'running', ['started_at' => now()->subHour()]);

    expect(app(ProgrammeSync::class)->queue($theatre))->not->toBeNull();
});

// Alerts

it('flags a failed run, an empty run and a stale theatre, and leaves a healthy one alone', function () {
    $failed = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Провален']);
    run($failed, 'failed', ['error' => 'HTTP 500']);
    $empty = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Празен']);
    run($empty, 'succeeded', ['performances_found' => 0]);
    $stale = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Стар']);
    run($stale, 'succeeded', ['started_at' => now()->subHours(40)]);
    $healthy = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Здрав']);
    run($healthy, 'succeeded');

    $alerts = collect(SyncAlerts::current())->mapWithKeys(fn ($a) => [$a['theatre']->name => implode(' ', $a['reasons'])]);

    expect($alerts->keys()->sort()->values()->all())->toBe(['Празен', 'Провален', 'Стар'])
        ->and($alerts['Провален'])->toContain('неуспешна')->toContain('HTTP 500')
        ->and($alerts['Празен'])->toContain('нито едно представление')
        ->and($alerts['Стар'])->toContain('40 часа');
});

it('shows alerts on the dashboard and a badge on the sync log', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'csv', 'name' => 'Провален']);
    run($theatre, 'failed', ['error' => 'HTTP 500']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Проблеми със синхронизацията')
        ->assertSee('HTTP 500');

    expect(\App\Filament\Resources\AdapterRuns\AdapterRunResource::getNavigationBadge())->toBe('1');
});

it('shows no alert box when every theatre is healthy', function () {
    run(Theatre::factory()->create(['adapter' => 'csv']), 'succeeded');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('Проблеми със синхронизацията');

    expect(\App\Filament\Resources\AdapterRuns\AdapterRunResource::getNavigationBadge())->toBeNull();
});

// Schedule

function nightlySync(): \Illuminate\Console\Scheduling\Event
{
    return SyncSchedule::register(new Schedule('Europe/Sofia'));
}

it('runs at 03:15 by default and moves when the time changes', function () {
    expect(nightlySync()->expression)->toBe('15 3 * * *');

    SyncSchedule::save('04:30', false);

    expect(nightlySync()->expression)->toBe('30 4 * * *')
        ->and(SyncSchedule::nextRun()->format('H:i'))->toBe('04:30')
        ->and(SyncSchedule::summary())->toContain('04:30');
});

it('skips the nightly run while paused', function () {
    SyncSchedule::save('03:15', true);

    expect(nightlySync()->filtersPass(app()))->toBeFalse()
        ->and(SyncSchedule::nextRun())->toBeNull()
        ->and(SyncSchedule::summary())->toContain('пауза');

    SyncSchedule::save('03:15', false);

    expect(nightlySync()->filtersPass(app()))->toBeTrue();
});

it('changes the schedule from the sync log', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListAdapterRuns::class)
        ->callAction('schedule', data: ['time' => '05:45', 'paused' => true])
        ->assertHasNoActionErrors();

    expect(SyncSchedule::time())->toBe('05:45')->and(SyncSchedule::paused())->toBeTrue();
});

it('rejects a time that is not HH:MM', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListAdapterRuns::class)
        ->callAction('schedule', data: ['time' => '25:00', 'paused' => false])
        ->assertHasActionErrors(['time']);

    expect(SyncSchedule::time())->toBe('03:15');
});

it('shows the next run on the sync log', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/adapter-runs')
        ->assertOk()
        ->assertSee('Нощна синхронизация всеки ден в 03:15');
});
