<?php

namespace App\Support;

use App\Models\Device;
use App\Models\DeviceIpAddress;
use App\Models\DeviceVisit;
use Carbon\CarbonInterface;

/**
 * Single source of truth for "is this device active right now, and what was
 * it last doing" — used by DeviceList, UserDetail (name badge + Devices tab),
 * and anywhere else that needs to answer "active or offline".
 *
 * A device counts as active if ANY of its three activity signals
 * (last_active_at, most recent IP hit, most recent visit) falls within the
 * last 5 minutes. The signal is also which "last seen via" label to show —
 * whichever of IP-hit or visit is more recent wins, matching the source the
 * device was actually last seen through.
 */
class DeviceActivity
{
    public const ACTIVE_WINDOW_MINUTES = 5;

    public static function threshold(): CarbonInterface
    {
        return now()->subMinutes(self::ACTIVE_WINDOW_MINUTES);
    }

    public static function isActive(Device $device): bool
    {
        return self::lastSeenAt($device) !== null
            && self::lastSeenAt($device)->greaterThanOrEqualTo(self::threshold());
    }

    /**
     * The most recent of the device's three activity timestamps, or null if
     * it has never been seen at all.
     */
    public static function lastSeenAt(Device $device): ?CarbonInterface
    {
        return self::lastSeenSource($device)['at'] ?? null;
    }

    /**
     * @return array{at: ?CarbonInterface, via: ?string, detail: ?string}
     *   via is 'ip' | 'visit' | 'touch' | null; detail is the IP address or
     *   visited URL that produced it, for display ("via IP: 1.2.3.4" /
     *   "via Visit: /shop").
     */
    public static function lastSeenSource(Device $device): array
    {
        $candidates = [];

        if ($device->last_active_at) {
            $candidates[] = ['at' => $device->last_active_at, 'via' => 'touch', 'detail' => null];
        }

        $lastIp = DeviceIpAddress::where('device_id', $device->id)
            ->orderByDesc('last_seen_at')
            ->first(['ip_address', 'last_seen_at']);

        if ($lastIp) {
            $candidates[] = ['at' => $lastIp->last_seen_at, 'via' => 'ip', 'detail' => $lastIp->ip_address];
        }

        $lastVisit = DeviceVisit::where('device_id', $device->id)
            ->orderByDesc('created_at')
            ->first(['url', 'created_at']);

        if ($lastVisit) {
            $candidates[] = ['at' => $lastVisit->created_at, 'via' => 'visit', 'detail' => $lastVisit->url];
        }

        if (empty($candidates)) {
            return ['at' => null, 'via' => null, 'detail' => null];
        }

        usort($candidates, fn ($a, $b) => $b['at'] <=> $a['at']);

        return $candidates[0];
    }

    public static function label(Device $device): string
    {
        $source = self::lastSeenSource($device);

        if (! $source['at']) {
            return 'Never seen';
        }

        $when = self::isActive($device) ? 'Active now' : $source['at']->diffForHumans();

        return match ($source['via']) {
            'ip'    => "{$when} · via IP {$source['detail']}",
            'visit' => "{$when} · via visit to {$source['detail']}",
            default => $when,
        };
    }
}
