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
        'theatro' => App\Adapters\TheatroAdapter::class,
        'vazrajdane' => App\Adapters\VazrajdaneAdapter::class,
        'zadkanala' => App\Adapters\ZadKanalaAdapter::class,
        'sofia' => App\Adapters\SofiaTheatreAdapter::class,
        'salzaismyah' => App\Adapters\SalzaISmyahAdapter::class,
        'mlt' => App\Adapters\MladezhkiAdapter::class,
        'theatre199' => App\Adapters\Theatre199Adapter::class,
        'satirata' => App\Adapters\SatirataAdapter::class,
    ],

    // Sent with every request to theatre sites, so they can see who we are and reach us.
    'user_agent' => env('TEATAR_USER_AGENT', 'TeatarBot/0.1 (+https://github.com/pkaratanev/teatar; theatre listings app)'),

    // Days before a watchlisted production's next date that the reminder push goes out.
    'watchlist_reminder_days' => 7,

];
