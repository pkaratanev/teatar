<?php

namespace App\Filament\Resources\Productions\Pages;

use App\Filament\Resources\ProductionReviews\ProductionReviewResource;
use App\Filament\Resources\Productions\ProductionResource;
use App\Enums\CatalogueSource;
use App\Models\Production;
use App\Services\Credits;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\EditRecord;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->pasteCreditsAction(), ProductionReviewResource::reviewedAction(), DeleteAction::make()];
    }

    /** "Внеси екип": paste the team and cast as a theatre's page lists them; saved straight away. */
    protected function pasteCreditsAction(): Action
    {
        return Action::make('pasteCredits')
            ->label('Внеси екип')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->modalHeading('Внеси екип и актьори')
            ->modalDescription('По един етикет на ред, имената след него, разделени със запетая или на отделни редове. '
                .'Персонаж в скоби или след „ - “. Съществуващите хора се използват отново; нищо не се изтрива и не се дублира. '
                .'Двама с едно име: „Стоян Радев #12“ избира човек #12 (номерът е в Каталог → Хора), „Стоян Радев #нов“ създава нов.')
            ->modalSubmitActionLabel('Добави')
            ->schema([
                Textarea::make('text')->hiddenLabel()->required()->rows(12)
                    ->placeholder("Режисьор: Иван Петров\nСценография и костюми: Мария Иванова\nМузика: Петър Димов\nАктьори: Анна Колева (Офелия), Петър Стоянов"),
            ])
            ->action(function (array $data, Credits $credits): void {
                /** @var Production $production */
                $production = $this->getRecord();
                $parsed = $credits->parse($data['text']);
                $added = $credits->add($production, $parsed, CatalogueSource::Admin);

                $notification = Notification::make()
                    ->title("Добавени участия: {$added}")
                    ->status($parsed['skipped'] ? 'warning' : 'success');
                if ($parsed['skipped'] !== []) {
                    $notification->body('Пропуснати редове (непознат етикет): '.implode(' · ', $parsed['skipped']))->persistent();
                }
                $notification->send();

                $this->redirect(ProductionResource::getUrl('edit', ['record' => $production]));
            });
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
