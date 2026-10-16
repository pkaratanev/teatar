<?php

use App\Jobs\SendWatchlistReminders;
use App\Models\AdapterRun;
use App\Support\SyncSchedule;
use Illuminate\Support\Facades\Schedule;

// Nightly programme sync for every theatre with an adapter. Time and pause are set in the admin (Синхронизации → График).
SyncSchedule::register(Schedule::getFacadeRoot());

// Runs that died mid-way (worker killed, timeout) are marked failed instead of "running" forever.
Schedule::call(fn () => AdapterRun::markStale())->everyTenMinutes()->name('mark-stale-sync-runs');

// Watchlist reminders in the morning, after the sync has the latest dates.
Schedule::job(new SendWatchlistReminders)->dailyAt('10:00')->timezone('Europe/Sofia');
