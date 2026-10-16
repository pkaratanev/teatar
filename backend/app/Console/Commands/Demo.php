<?php

namespace App\Console\Commands;

use App\Jobs\SyncTheatreProgramme;
use App\Models\Performance;
use App\Models\Theatre;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoCatalogueSeeder;
use Illuminate\Console\Command;

/**
 * One command to get a realistic local setup for testing the app:
 * theatres + admin, the real programme from the theatres' sites (or fake
 * productions when offline), then demo people with ratings, reviews,
 * diaries, watchlists, follows and likes.
 */
class Demo extends Command
{
    protected $signature = 'teatar:demo {--offline : Skip the theatre sites and use fake productions}';

    protected $description = 'Set up local test data: real programme plus demo users and activity';

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('teatar:demo is for local testing only.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        if (! $this->option('offline')) {
            $this->info('Pulling the real programme from the theatres (about a minute)…');
            foreach (Theatre::whereNotNull('adapter')->where('is_active', true)->get() as $theatre) {
                SyncTheatreProgramme::dispatchSync($theatre);
                $run = $theatre->adapterRuns()->latest('started_at')->first();
                $this->line(sprintf('  %-45s %s', $theatre->name, $run?->status === 'succeeded'
                    ? "{$run->performances_found} performances"
                    : 'failed: '.mb_substr((string) $run?->error, 0, 80)));
            }
        }

        if (Performance::count() === 0) {
            $this->warn('No real programme available; using fake productions instead.');
            $this->call('db:seed', ['--class' => DemoCatalogueSeeder::class, '--force' => true]);
        }

        $this->call('db:seed', ['--class' => DemoActivitySeeder::class, '--force' => true]);

        $this->newLine();
        $this->info('Ready. Sign in to the app or /admin as admin@teatar.test / password.');
        $this->line('Demo people: maria.p@demo.teatar.test, gogo@demo.teatar.test, … (password: password)');

        return self::SUCCESS;
    }
}
