<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'label',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Placeholders every template can use, whatever sent it. Shown on the
     * SMS Templates admin page, so keep the descriptions short.
     *
     * @return array<string, string> placeholder => description
     */
    public static function globalPlaceholders(): array
    {
        return [
            'site_name' => 'Site Name from Site Settings',
            'site_url' => 'Website address',
            'support_phone' => 'Support phone from Site Settings → Contact',
            'year' => 'Current year',
        ];
    }

    /** @return array<string, string> */
    protected static function globalValues(): array
    {
        $siteName = (string) Setting::get('site_name', config('app.name'));

        return [
            'site_name' => $siteName,
            'app_name' => $siteName, // older templates used {app_name}
            // url() follows the live request's host; in queue workers it falls back to APP_URL.
            'site_url' => rtrim(url('/'), '/'),
            'support_phone' => (string) Setting::get('support_phone', '', 'contact'),
            'year' => (string) now()->year,
        ];
    }

    public function render(array $data = []): string
    {
        // Caller data wins over the global values.
        $values = array_merge(static::globalValues(), $data);
        $supplied = fn (array $m) => array_key_exists($m[1], $values) ? $m[0] : '';

        // A placeholder the sender didn't supply must not reach the customer as
        // "{name}" — drop it with the space that would be left dangling
        // ("Hi {name}, your" -> "Hi, your"; "Visit {x} now" -> "Visit now").
        // Done on the template text before substituting, so values are never touched.
        $body = preg_replace_callback('/[ \t]*\{([a-z_]+)\}(?=[ \t]*(?:[,.!?]|$))/m', $supplied, $this->body);
        $body = preg_replace_callback('/\{([a-z_]+)\}[ \t]*/', $supplied, $body);

        // Single pass, so a value containing "{...}" is never substituted again.
        $map = [];
        foreach ($values as $placeholder => $value) {
            $map['{' . $placeholder . '}'] = is_scalar($value) || $value === null ? (string) $value : '';
        }

        return trim(strtr($body, $map));
    }
}
