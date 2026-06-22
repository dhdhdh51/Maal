<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public const CACHE_KEY = 'maal.settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Get a typed setting value with a fallback default.
     *
     * The full settings map is cached as a plain array (key => [value, type])
     * so it survives serialization across cache drivers/processes.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $s) => [$s->key => ['value' => $s->value, 'type' => $s->type]])
                ->all(),
        );

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        return self::castValue($all[$key]['value'], $all[$key]['type']);
    }

    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'type' => $type,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]
        );
    }

    public function typedValue(): mixed
    {
        return self::castValue($this->value, $this->type);
    }
}
