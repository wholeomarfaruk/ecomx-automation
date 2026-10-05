<?php

namespace App\OrderIntake\Ai;

use RuntimeException;

/**
 * The AI fallback didn't produce usable data — HTTP/timeout/rate-limit
 * failure, or a reply that isn't valid against the order schema. The
 * intake keeps the parser's drafts and shows this message.
 */
class AiExtractionException extends RuntimeException
{
    /** @param array<string, mixed> $usage what the failed call still cost, when known */
    public function __construct(string $message, public readonly ?string $model = null, public readonly array $usage = [], public readonly ?int $latencyMs = null)
    {
        parent::__construct($message);
    }
}
