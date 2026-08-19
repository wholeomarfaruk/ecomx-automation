<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Customer;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Central place that answers "is this visitor blocked, at this scope?".
 *
 * A block can target an IP address, a Device, a Customer, or a User. Customer
 * and User blocks cascade to every Device linked to them (customer_id /
 * user_id) — blocking a customer blocks every device that customer has ever
 * browsed from, without needing a block row per device.
 *
 * Results are cached briefly per (identity, scope) so this check — which
 * runs on every request via EnforceBlocks — doesn't add a fresh set of
 * queries to every single page load.
 */
class BlockGuard
{
    protected const CACHE_TTL_SECONDS = 60;

    public function isBlocked(Request $request, string $scope): bool
    {
        $ip = $request->ip();
        /** @var Device|null $device */
        $device = $request->attributes->get('device');

        if ($ip && $this->ipBlocked($ip, $scope)) {
            return true;
        }

        if ($device && $this->deviceBlocked($device, $scope)) {
            return true;
        }

        if ($device?->customer_id && $this->targetBlocked(Customer::class, $device->customer_id, $scope)) {
            return true;
        }

        if (auth()->check() && $this->targetBlocked(User::class, auth()->id(), $scope)) {
            return true;
        }

        return false;
    }

    protected function ipBlocked(string $ip, string $scope): bool
    {
        return $this->remember("ip:{$ip}:{$scope}", fn () => Block::applicable()
            ->forScope($scope)
            ->where('ip_address', $ip)
            ->exists());
    }

    protected function deviceBlocked(Device $device, string $scope): bool
    {
        // A device inherits its owning customer/user's block too, in case
        // the request didn't carry an authenticated session this time.
        if ($device->customer_id && $this->targetBlocked(Customer::class, $device->customer_id, $scope)) {
            return true;
        }

        if ($device->user_id && $this->targetBlocked(User::class, $device->user_id, $scope)) {
            return true;
        }

        return $this->targetBlocked(Device::class, $device->id, $scope);
    }

    protected function targetBlocked(string $type, int $id, string $scope): bool
    {
        return $this->remember("target:{$type}:{$id}:{$scope}", fn () => Block::applicable()
            ->forScope($scope)
            ->where('blockable_type', $type)
            ->where('blockable_id', $id)
            ->exists());
    }

    protected function remember(string $key, \Closure $callback): bool
    {
        return Cache::remember('block_guard:' . $key, self::CACHE_TTL_SECONDS, $callback);
    }

    /**
     * Call after creating/toggling/deleting a block so the change takes
     * effect immediately instead of waiting out the cache TTL.
     */
    public static function forget(string $type, int $id, ?string $scope = null): void
    {
        $scopes = $scope ? [$scope] : Block::SCOPES;

        foreach ($scopes as $s) {
            Cache::forget("block_guard:target:{$type}:{$id}:{$s}");
        }
    }

    public static function forgetIp(string $ip, ?string $scope = null): void
    {
        $scopes = $scope ? [$scope] : Block::SCOPES;

        foreach ($scopes as $s) {
            Cache::forget("block_guard:ip:{$ip}:{$s}");
        }
    }
}
