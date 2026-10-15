<?php

namespace App\Filament\Widgets;

use App\Enums\CreditRole;
use App\Filament\Resources\Productions\ProductionResource;
use App\Models\Production;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Productions playing soon that are missing author, director, cast or poster — the admin's to-do list. */
class NeedsData extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Липсващи данни (играят в следващите 30 дни)')
            ->query(
                Production::query()
                    ->with('theatre')
                    ->whereHas('performances', fn (Builder $q) => $q->where('starts_at', '>=', now())->where('starts_at', '<=', now()->addDays(30))->where('is_cancelled', false))
                    ->where(fn (Builder $q) => $q
                        ->whereNull('author')
                        ->orWhereDoesntHave('credits', fn (Builder $c) => $c->where('role_type', CreditRole::Director))
                        ->orWhereDoesntHave('credits', fn (Builder $c) => $c->where('role_type', CreditRole::Actor))
                        ->orWhereDoesntHave('media', fn (Builder $m) => $m->where('collection_name', 'poster')))
                    ->withExists([
                        'credits as has_director' => fn (Builder $c) => $c->where('role_type', CreditRole::Director),
                        'credits as has_cast' => fn (Builder $c) => $c->where('role_type', CreditRole::Actor),
                        'media as has_poster' => fn (Builder $m) => $m->where('collection_name', 'poster'),
                    ])
                    ->withMin(['performances as next_at' => fn (Builder $q) => $q->where('starts_at', '>=', now())->where('is_cancelled', false)], 'starts_at')
            )
            ->defaultSort('next_at')
            ->columns([
                TextColumn::make('title')->label('Постановка')->searchable(),
                TextColumn::make('theatre.name')->label('Театър'),
                TextColumn::make('next_at')->label('Следващо')->dateTime('d.m H:i', config('app.theatre_timezone')),
                IconColumn::make('author')->label('Автор')->boolean()->state(fn (Production $record) => $record->author !== null),
                IconColumn::make('has_director')->label('Режисьор')->boolean(),
                IconColumn::make('has_cast')->label('Актьори')->boolean(),
                IconColumn::make('has_poster')->label('Плакат')->boolean(),
            ])
            ->recordActions([
                Action::make('edit')->label('Попълни')->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Production $record) => ProductionResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
