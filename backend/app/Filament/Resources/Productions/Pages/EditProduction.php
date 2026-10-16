<?php

namespace App\Filament\Resources\Productions\Pages;

use App\Filament\Resources\ProductionReviews\ProductionReviewResource;
use App\Filament\Resources\Productions\ProductionResource;
use App\Models\Production;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [ProductionReviewResource::reviewedAction(), DeleteAction::make()];
    }

    /** Under the title: is it on the review list, or who checked it last. */
    public function getSubheading(): ?string
    {
        /** @var Production $production */
        $production = $this->getRecord();

        if ($production->needs_review) {
            $missing = $production->missingData();

            return 'За преглед ('.($production->review_reason === 'changed' ? 'променена от импорта' : 'нова').')'
                .($missing ? ' · липсва: '.mb_strtolower(implode(', ', $missing)) : '');
        }

        return $production->reviewed_at
            ? 'Прегледана '.$production->reviewed_at->setTimezone(config('app.theatre_timezone'))->format('d.m.Y H:i')
                .($production->reviewer ? " от {$production->reviewer->name}" : '')
            : null;
    }
}
