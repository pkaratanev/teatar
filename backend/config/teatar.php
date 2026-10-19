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
        'dramavarna' => App\Adapters\DramaVarnaAdapter::class,
        'vnpuppet' => App\Adapters\PuppetVarnaAdapter::class,
    ],

    // Team and cast from each production's own page on the theatre's site (App\Services\CastSync), after a sync.
    // per_run: pages per theatre per sync (0 turns it off; tests set 0 unless they test it).
    'cast_reader' => [
        'per_run' => (int) env('TEATAR_CAST_READER_PER_RUN', 25),
        'readers' => [
            'vazrajdane' => App\Adapters\Cast\VazrajdaneCastReader::class,
            'zadkanala' => App\Adapters\Cast\ZadKanalaCastReader::class,
            'tba' => App\Adapters\Cast\TbaCastReader::class,
            'theatro' => App\Adapters\Cast\TheatroCastReader::class,
            'national' => App\Adapters\Cast\NationalCastReader::class,
            'theatre199' => App\Adapters\Cast\Theatre199CastReader::class,
            'satirata' => App\Adapters\Cast\SatirataCastReader::class,
            'sofia' => App\Adapters\Cast\SofiaTheatreCastReader::class,
            'salzaismyah' => App\Adapters\Cast\SalzaISmyahCastReader::class,
            'dramavarna' => App\Adapters\Cast\DramaVarnaCastReader::class,
        ],
    ],

    // Sent with every request to theatre sites, so they can see who we are and reach us.
    'user_agent' => env('TEATAR_USER_AGENT', 'TeatarBot/0.1 (+https://github.com/pkaratanev/teatar; theatre listings app)'),

    // Adapters whose site blocks datacenter IPs with a bot check (Cloudflare).
    // The live source check reports them as warnings instead of failures.
    // We never try to get around such checks.
    'known_blocked' => ['mlt'],

    // Shown on /privacy and /delete-account as the address for data and deletion requests.
    'contact_email' => env('TEATAR_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Days before a watchlisted production's next date that the reminder push goes out.
    'watchlist_reminder_days' => 7,

];
