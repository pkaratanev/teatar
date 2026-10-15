<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The moderation queue: reported reviews float to the top; a moderator hides
 * a review, restores it, or marks the reports as resolved.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Общност';

    protected static ?string $modelLabel = 'ревю';

    protected static ?string $pluralModelLabel = 'ревюта';

    public static function getNavigationBadge(): ?string
    {
        $open = Review::has('openReports')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['user', 'production'])->withCount('openReports'))
            ->columns([
                TextColumn::make('production.title')->label('Постановка')->searchable(),
                TextColumn::make('user.username')->label('Потребител')->searchable(),
                TextColumn::make('body')->label('Текст')->limit(80)->wrap(),
                IconColumn::make('has_spoilers')->label('Спойлери')->boolean(),
                TextColumn::make('open_reports_count')->label('Сигнали')->badge()->color('danger')->sortable(),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('created_at')->label('Публикувано')->since()->sortable(),
            ])
            ->defaultSort('open_reports_count', 'desc')
            ->filters([
                Filter::make('reported')->label('Само със сигнали')->default()
                    ->query(fn (Builder $q) => $q->has('openReports')),
                SelectFilter::make('status')->label('Статус')->options(ReviewStatus::class),
            ])
            ->recordActions([
                Action::make('hide')->label('Скрий')->icon(Heroicon::OutlinedEyeSlash)->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record) => $record->status === ReviewStatus::Visible)
                    ->action(function (Review $record) {
                        $record->update(['status' => ReviewStatus::Hidden]);
                        $record->openReports()->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);
                    }),
                Action::make('restore')->label('Покажи')->icon(Heroicon::OutlinedEye)
                    ->visible(fn (Review $record) => $record->status === ReviewStatus::Hidden)
                    ->action(fn (Review $record) => $record->update(['status' => ReviewStatus::Visible])),
                Action::make('dismiss')->label('Отхвърли сигналите')->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (Review $record) => $record->open_reports_count > 0)
                    ->action(fn (Review $record) => $record->openReports()
                        ->update(['resolved_at' => now(), 'resolved_by' => auth()->id()])),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
