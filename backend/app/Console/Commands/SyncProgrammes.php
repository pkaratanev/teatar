<?php

namespace App\Console\Commands;

use App\Jobs\SyncTheatreProgramme;
use App\Services\ProgrammeSync;
use Illuminate\Console\Command;

class SyncProgrammes extends Command
{
    protected $signature = 'teatar:sync-programmes
                            {theatre? : One theatre, by its slug or its adapter (e.g. zadkanala); all active theatres with an adapter if omitted}
                            {--now : Run in this process instead of queueing}';

    protected $description = 'Pull upcoming performances from each theatre\'s own programme source';

    public function handle(ProgrammeSync $sync): int
    {
        $theatres = ProgrammeSync::theatres()
            ->when($this->argument('theatre'), fn ($q, $key) => $q->where(fn ($q) => $q->where('slug', $key)->orWhere('adapter', $key)))
            ->get();

        if ($theatres->isEmpty()) {
            $this->warn('No matching theatre has an adapter configured. Use a theatre slug or an adapter: '.implode(', ', array_keys((array) config('teatar.adapters'))).'.');

            return self::SUCCESS;
        }

        foreach ($theatres as $theatre) {
            if ($this->option('now')) {
                SyncTheatreProgramme::dispatchSync($theatre);
                $this->line("Synced {$theatre->name}");
            } elseif ($sync->queue($theatre)) {
                $this->line("Queued {$theatre->name}");
            } else {
                $this->line("Skipped {$theatre->name}: already queued or running");
            }
        }

        return self::SUCCESS;
    }
}
