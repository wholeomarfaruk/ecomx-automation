<?php

namespace App\Marketing\Destinations;

use App\Marketing\Contracts\MarketingDestinationContract;
use App\Marketing\Destinations\Meta\MetaAdapter;
use InvalidArgumentException;

/**
 * Only 'meta' is registered — Google/TikTok/webhook adapters don't exist
 * yet. Resolving an unregistered key throws rather than silently no-op'ing,
 * so a typo'd or not-yet-built destination fails loudly instead of quietly
 * dropping events.
 */
final class DestinationRegistry
{
    /** @var array<string, class-string<MarketingDestinationContract>> */
    private const ADAPTERS = [
        'meta' => MetaAdapter::class,
    ];

    public function has(string $key): bool
    {
        return isset(self::ADAPTERS[$key]);
    }

    public function get(string $key): MarketingDestinationContract
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown marketing destination: {$key}");
        }

        return app(self::ADAPTERS[$key]);
    }

    /** @return string[] */
    public function keys(): array
    {
        return array_keys(self::ADAPTERS);
    }
}
