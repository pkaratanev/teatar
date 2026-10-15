<?php

return [

    /*
     * Programme adapters, keyed by the value stored in theatres.adapter.
     * Add one class per theatre site in app/Adapters as the launch set grows.
     */
    'adapters' => [
        'csv' => App\Adapters\CsvProgrammeAdapter::class,
        'tba' => App\Adapters\TbaAdapter::class,
        'national' => App\Adapters\NationalTheatreAdapter::class,
    ],

    // Days before a watchlisted production's next date that the reminder push goes out.
    'watchlist_reminder_days' => 7,

];
