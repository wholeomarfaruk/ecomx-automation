<?php

namespace App\Courier;

use App\Enums\Sales\CourierStatus;

/**
 * Every courier uses its own vocabulary for the same real-world event
 * (SteadFast's "in_review" vs RedX's "pending-pickup" vs Pathao's
 * "Pickup_Requested" all mean roughly "pending"). Each driver maps its raw
 * strings into App\Enums\Sales\CourierStatus through this normalizer so
 * reporting and order management only ever deal with one status vocabulary.
 */
class CourierStatusNormalizer
{
    /**
     * @param array<string, CourierStatus> $map raw provider status (lowercased) => normalized status
     */
    public static function normalize(string $rawStatus, array $map, CourierStatus $default = CourierStatus::PENDING): CourierStatus
    {
        $key = strtolower(trim($rawStatus));

        return $map[$key] ?? $default;
    }
}
