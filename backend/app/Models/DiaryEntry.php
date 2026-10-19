<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $performance_id
 * @property \Illuminate\Support\Carbon $seen_on
 */
#[Fillable(['user_id', 'production_id', 'performance_id', 'seen_on'])]
class DiaryEntry extends Model
{
    protected function casts(): array
    {
        return ['seen_on' => 'date'];
    }

    /**
     * The visit on that day, created or updated. Matches the day with whereDate: SQLite keeps a
     * `date` cast as "2026-04-09 00:00:00", so comparing to "2026-04-09" would miss the row and
     * the insert would hit the unique index.
     *
     * @param  array<string, mixed>  $values
     */
    public static function record(int $userId, int $productionId, string $day, array $values = []): self
    {
        $entry = self::query()
            ->where('user_id', $userId)
            ->where('production_id', $productionId)
            ->whereDate('seen_on', $day)
            ->first();

        if ($entry) {
            $entry->update($values);

            return $entry;
        }

        return self::create(['user_id' => $userId, 'production_id' => $productionId, 'seen_on' => $day, ...$values]);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Production, $this> */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /** @return BelongsTo<Performance, $this> */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
