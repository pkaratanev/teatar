<?php

namespace App\Enums;

/**
 * Where a catalogue row came from. Never "theatre.art.bg": that catalogue is
 * protected by the EU database right and must not be extracted from.
 */
enum CatalogueSource: string
{
    case Admin = 'admin';           // typed in Filament
    case Adapter = 'adapter';       // a theatre's own website, via App\Adapters
    case TheatreFeed = 'feed';      // a feed or spreadsheet the theatre sent us
    case Community = 'community';   // submitted by a user, after moderation
}
