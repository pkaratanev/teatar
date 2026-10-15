<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Общност';

    protected static ?string $modelLabel = 'потребител';

    protected static ?string $pluralModelLabel = 'потребители';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->label('Потребител')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('reviews_count')->label('Ревюта')->counts('reviews')->sortable(),
                IconColumn::make('is_admin')->label('Админ')->boolean(),
                TextColumn::make('shadow_banned_at')->label('Shadow ban')->since(),
                TextColumn::make('created_at')->label('Регистриран')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('shadow_banned_at')->label('Shadow ban')->nullable(),
            ])
            ->recordActions([
                Action::make('shadowBan')->label('Shadow ban')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Ревютата на потребителя ще се виждат само от него. Той няма да бъде уведомен.')
                    ->visible(fn (User $record) => ! $record->isShadowBanned() && ! $record->is_admin)
                    ->action(fn (User $record) => $record->forceFill(['shadow_banned_at' => now()])->save()),
                Action::make('lift')->label('Махни ban')->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (User $record) => $record->isShadowBanned())
                    ->action(fn (User $record) => $record->forceFill(['shadow_banned_at' => null])->save()),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
