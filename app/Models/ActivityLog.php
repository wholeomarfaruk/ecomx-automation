<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    protected $table = 'activity_log';

    public static function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['browser' => null, 'os' => null];
        }

        if (str_contains($ua, 'Edg/'))          $browser = 'Edge';
        elseif (str_contains($ua, 'OPR/'))      $browser = 'Opera';
        elseif (str_contains($ua, 'Chrome/'))   $browser = 'Chrome';
        elseif (str_contains($ua, 'Firefox/'))  $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari/'))   $browser = 'Safari';
        else                                    $browser = 'Other';

        if (str_contains($ua, 'Windows'))       $os = 'Windows';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
        elseif (str_contains($ua, 'Android'))   $os = 'Android';
        elseif (str_contains($ua, 'Macintosh')) $os = 'macOS';
        elseif (str_contains($ua, 'Linux'))     $os = 'Linux';
        else                                    $os = 'Other';

        return compact('browser', 'os');
    }
}
