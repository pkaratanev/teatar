<?php

namespace App\Console\Commands;

use App\Jobs\SyncTheatreProgramme;
use App\Models\Theatre;
use Illuminate\Console\Command;

class SyncProgrammes extends Command
{
    protected $signature = 'teatar:sync-programmes
                            {theatre? : Slug of one theatre; all active theatres with an adapter if omitted}
                            {--now : Run in this process instead of queueing}';

    protected $description = 'Pull upcoming performances from each theatre\'s own programme source';

    public function handle(): int
    {
        $theatres = Theatre::query()
            ->where('is_active', true)
            ->whereNotNull('adapter')
            ->when($this->argument('theatre'), fn ($q, $slug) => $q->where('slug', $slug))
            ->get();

        if ($theatres->isEmpty()) {
            $this->warn('No matching theatre has an adapter configured.');

            return self::SUCCESS;
        }

        foreach ($theatres as $theatre) {
            $this->option('now')
                ? SyncTheatreProgramme::dispatchSync($theatre)
                : SyncTheatreProgramme::dispatch($theatre);

            $this->line(($this->option('now') ? 'Synced ' : 'Queued ').$theatre->name);
        }

        return self::SUCCESS;
    }
}
