<?php

namespace App\Jobs;

use App\Services\QueueHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * No-op job sent from Site Settings → Queue ("Send test job"): when a worker
 * picks it up it marks itself processed, proving end to end that the
 * default queue is being worked.
 */
class QueueHealthCheckJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public string $token,
    ) {
    }

    public function handle(): void
    {
        QueueHealthService::markTestJobProcessed($this->token);
    }
}
