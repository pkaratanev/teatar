<?php

use Illuminate\Support\Facades\Schedule;

// Nightly programme sync for every theatre with an adapter.
Schedule::command('teatar:sync-programmes')->dailyAt('03:15')->timezone('Europe/Sofia');
