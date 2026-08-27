<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MarketingUtmRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('marketing_utm_rules_map'));
        static::deleted(fn () => Cache::forget('marketing_utm_rules_map'));
    }

    /**
     * Returns the normalized value for a raw UTM value on the given field,
     * or the original value unchanged if no active rule matches.
     * Matching is case-insensitive. Runs on every tracked request, so the
     * full rule set is cached as a single lookup map rather than querying
     * per field/value.
     */
    public static function normalize(string $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $map = Cache::rememberForever('marketing_utm_rules_map', function () {
            return static::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->get()
                ->groupBy('field')
                ->map(fn ($rules) => $rules->keyBy(fn ($rule) => strtolower($rule->match_value)));
        });

        return $map->get($field)?->get(strtolower($value))?->normalized_value ?? $value;
    }
}
