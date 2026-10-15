<?php

namespace App\Filament\Resources\Theatres\Pages;

use App\Filament\Resources\Theatres\TheatreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTheatres extends ManageRecords
{
    protected static string $resource = TheatreResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
