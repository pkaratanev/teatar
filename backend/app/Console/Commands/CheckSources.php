<?php

namespace App\Console\Commands;

use App\Adapters\ScrapedPerformance;
use App\Jobs\SyncTheatreProgramme;
use App\Models\Theatre;
use Illuminate\Console\Command;
use Throwable;

/**
 * Runs every theatre adapter against the live site without saving anything,
 * and reports what each one found. Used by the "Live sources" CI workflow
 * and handy locally after a theatre changes its site.
 */
class CheckSources extends Command
{
    protected $signature = 'teatar:check-sources {theatre? : Slug of one theatre}';

    protected $description = 'Fetch every theatre programme from the live sites and report counts, without saving';

    public function handle(): int
    {
        $theatres = Theatre::query()
            ->whereNotNull('adapter')
            ->when($this->argument('theatre'), fn ($q, $slug) => $q->where('slug', $slug))
            ->orderBy('name')
            ->get();

        $inCi = (bool) getenv('GITHUB_ACTIONS');
        $failures = 0;
        $rows = [];

        foreach ($theatres as $theatre) {
            try {
                /** @var list<ScrapedPerformance> $found */
                $found = iterator_to_array(SyncTheatreProgramme::resolveAdapter($theatre)->fetch($theatre), false);
                $dates = array_map(fn (ScrapedPerformance $p) => $p->startsAt->format('Y-m-d'), $found);
                $titles = count(array_unique(array_map(fn (ScrapedPerformance $p) => $p->title, $found)));
                $guests = count(array_filter($found, fn (ScrapedPerformance $p) => $p->guest()));
                $staged = count(array_filter($found, fn (ScrapedPerformance $p) => $p->stage !== null));

                $rows[] = [$theatre->name, count($found), $titles, $guests, $staged, $dates ? min($dates).' → '.max($dates) : '—', count($found) ? 'ok' : 'EMPTY'];

                if (count($found) === 0) {
                    $failures++;
                    $inCi && $this->line("::warning title={$theatre->name}::No performances found");
                } elseif ($inCi) {
                    $this->line("::notice title={$theatre->name}::".count($found)." performances, {$titles} productions, {$guests} guest, ".($dates ? min($dates).' to '.max($dates) : ''));
                }
            } catch (Throwable $e) {
                $failures++;
                $rows[] = [$theatre->name, 0, 0, 0, 0, '—', 'ERROR'];
                $message = str_replace(["\r", "\n"], ' ', mb_substr($e->getMessage(), 0, 500));
                $this->error("{$theatre->name}: {$message}");
                $inCi && $this->line("::error title={$theatre->name}::{$message}");
            }
        }

        $this->table(['Theatre', 'Performances', 'Productions', 'Guest', 'With stage', 'Dates', 'Status'], $rows);

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
