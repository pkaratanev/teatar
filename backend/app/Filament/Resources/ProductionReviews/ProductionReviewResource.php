<?php

namespace App\Filament\Resources\ProductionReviews;

use App\Filament\Resources\ProductionReviews\Pages\ListProductionReviews;
use App\Filament\Resources\Productions\ProductionResource;
use App\Models\Production;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * "За преглед": productions an importer created or added data to, waiting for an admin to check them and
 * fill what's missing before they're trusted. "Прегледано" records who checked and when; the next import
 * that adds or changes data puts the production back on the list.
 */
class ProductionReviewResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static ?string $slug = 'production-reviews';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $navigationLabel = 'За преглед';

    protected static ?string $modelLabel = 'постановка за преглед';

    protected static ?string $pluralModelLabel = 'За преглед';

    protected static ?int $navigationSort = 0;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('needs_review', true)
            ->with('theatre')
            ->scopes(['withDataChecks'])
            ->withMin(['performances as next_at' => fn (Builder $q) => $q->where('starts_at', '>=', now())->where('is_cancelled', false)], 'starts_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Нови или променени от импорта. Попълни липсващото и натисни „Прегледано“. Описанието и плакатът могат да липсват, ако театърът ги няма.')
            ->columns([
                TextColumn::make('title')->label('Постановка')->searchable()->wrap()
                    ->description(fn (Production $record) => $record->visiting_company ? "гостува {$record->visiting_company}" : null),
                TextColumn::make('theatre.name')->label('Театър')->wrap(),
                TextColumn::make('next_at')->label('Следващо')->sortable()
                    ->dateTime('d.m H:i', config('app.theatre_timezone'))->placeholder('няма дата'),
                TextColumn::make('review_reason')->label('Защо')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'changed' ? 'Променена' : 'Нова')
                    ->color(fn (?string $state) => $state === 'changed' ? 'warning' : 'info'),
                TextColumn::make('missing')->label('Липсва')->badge()->color('danger')
                    ->state(fn (Production $record) => $record->missingData())
                    ->placeholder('Нищо'),
                TextColumn::make('source_url')->label('Източник')->limit(30)
                    ->url(fn (Production $record) => $record->source_url, shouldOpenInNewTab: true)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('next_at')
            ->filters([
                Filter::make('soon')->label('Играе до 30 дни')->default()
                    ->query(fn (Builder $query) => $query->whereHas('performances', fn (Builder $q) => $q
                        ->where('starts_at', '>=', now())->where('starts_at', '<=', now()->addDays(30))->where('is_cancelled', false))),
                SelectFilter::make('theatre')->label('Театър')->relationship('theatre', 'name'),
                SelectFilter::make('review_reason')->label('Защо')->options(['new' => 'Нова', 'changed' => 'Променена']),
            ])
            ->recordActions([
                Action::make('fill')->label('Попълни')->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Production $record) => ProductionResource::getUrl('edit', ['record' => $record])),
                self::reviewedAction(),
            ])
            ->toolbarActions([
                BulkAction::make('reviewed')->label('Прегледано')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Маркира избраните постановки като прегледани от теб.')
                    ->action(function (Collection $records) {
                        foreach ($records as $production) {
                            if ($production instanceof Production) {
                                $production->markReviewed(auth()->id());
                            }
                        }
                        Notification::make()->title('Прегледани: '.$records->count())->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** Also on the production's edit page. */
    public static function reviewedAction(): Action
    {
        return Action::make('reviewed')->label('Прегледано')->icon(Heroicon::OutlinedCheck)->color('success')
            ->visible(fn (Production $record) => $record->needs_review)
            ->action(function (Production $record) {
                $record->markReviewed(auth()->id());
                Notification::make()->title("„{$record->title}“ е прегледана")->success()->send();
            });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Production::where('needs_review', true)
            ->whereHas('performances', fn (Builder $q) => $q->where('starts_at', '>=', now())->where('starts_at', '<=', now()->addDays(30))->where('is_cancelled', false))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'За преглед, играят до 30 дни';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListProductionReviews::route('/')];
    }
}
