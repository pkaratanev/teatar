<?php

namespace App\Filament\Resources\ProductionReviews\Pages;

use App\Filament\Resources\ProductionReviews\ProductionReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListProductionReviews extends ListRecords
{
    protected static string $resource = ProductionReviewResource::class;
}
