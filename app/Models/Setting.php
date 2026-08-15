<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'label'];

    public static function get(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        return Cache::rememberForever("setting:{$group}:{$key}", function () use ($key, $group, $default) {
            $setting = static::where('group', $group)->where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return static::decode($setting->value);
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => static::encode($value)]
        );
        Cache::forget("setting:{$group}:{$key}");
    }

    protected static function encode(mixed $value): mixed
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected static function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $value;
    }

    public static function getGroup(string $group): array
    {
        return Cache::rememberForever("settings:group:{$group}", function () use ($group) {
            return static::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    public static function forgetGroup(string $group): void
    {
        Cache::forget("settings:group:{$group}");
        static::where('group', $group)->each(function ($s) {
            Cache::forget("setting:{$s->group}:{$s->key}");
        });
    }
}
