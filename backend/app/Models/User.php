<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'bio', 'apple_id', 'google_id', 'expo_push_token'])]
#[Hidden(['password', 'remember_token', 'apple_id', 'google_id', 'expo_push_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'shadow_banned_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function isShadowBanned(): bool
    {
        return $this->shadow_banned_at !== null;
    }

    /** @return HasMany<Rating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<DiaryEntry, $this> */
    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    /** @return HasMany<WatchlistItem, $this> */
    public function watchlistItems(): HasMany
    {
        return $this->hasMany(WatchlistItem::class);
    }

    /** @return HasMany<Follow, $this> */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }
}
