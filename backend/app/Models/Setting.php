<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/** Small key/value settings an admin can change without a deploy (e.g. the nightly sync time). */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Null when unset, and also when the table doesn't exist yet: the scheduler reads settings
     * while artisan boots, which includes the very first `migrate` on a fresh database.
     */
    public static function read(string $key): ?string
    {
        try {
            $value = static::query()->whereKey($key)->value('value');

            return $value === null ? null : (string) $value;
        } catch (Throwable) {
            return null;
        }
    }

    public static function write(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
