<?php

use App\Jobs\SendWatchlistReminders;
use Illuminate\Support\Facades\Schedule;

// Nightly programme sync for every theatre with an adapter.
Schedule::command('teatar:sync-programmes')->dailyAt('03:15')->timezone('Europe/Sofia');

// Watchlist reminders in the morning, after the sync has the latest dates.
Schedule::job(new SendWatchlistReminders)->dailyAt('10:00')->timezone('Europe/Sofia');
