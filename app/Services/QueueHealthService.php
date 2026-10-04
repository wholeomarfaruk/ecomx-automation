<?php

namespace App\Services;

use App\Jobs\QueueHealthCheckJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Throwable;

/**
 * Answers "is the queue actually being worked?" for Site Settings → Queue.
 *
 * Workers leave a heartbeat in the cache (shared by the web and worker
 * processes), and the jobs / failed_jobs tables give the backlog. Without
 * this a missing or misconfigured worker (e.g. listening on the wrong
 * --queue) is invisible — jobs just pile up silently.
 */
final class QueueHealthService
{
    private const WORKER_SEEN = 'queue_health:worker_seen_at';
    private const LAST_PROCESSED = 'queue_health:last_processed';
    private const LAST_FAILED = 'queue_health:last_failed';
    private const TEST_JOB = 'queue_health:test_job';

    /** A cron-driven worker (--stop-when-empty) only starts once a minute, so allow some slack. */
    public const WORKER_STALE_AFTER_SECONDS = 180;

    /** Ready jobs waiting longer than this mean the worker can't keep up. */
    public const SLOW_AFTER_SECONDS = 600;

    public static function listen(): void
    {
        $lastBeat = 0;

        // Looping fires on every pass of `queue:work`, even when the queue is
        // empty, so it proves a worker is running — not just that some job
        // ran. Throttled per process so a busy worker doesn't write the
        // cache on every job. Must not return a value: the worker treats a
        // `false` from a Looping listener as "pause".
        Event::listen(Looping::class, function () use (&$lastBeat): void {
            if (time() - $lastBeat < 15) {
                return;
            }

            $lastBeat = time();
            self::safely(fn () => Cache::forever(self::WORKER_SEEN, time()));
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            self::safely(function () use ($event) {
                Cache::forever(self::LAST_PROCESSED, [
                    'job' => class_basename($event->job->resolveName()),
                    'queue' => $event->job->getQueue(),
                    'at' => time(),
                ]);
                self::bump('processed');
            });
        });

        // Only permanent failures — a job that will still be retried fires
        // JobExceptionOccurred instead and isn't counted here.
        Event::listen(JobFailed::class, function (JobFailed $event): void {
            self::safely(function () use ($event) {
                Cache::forever(self::LAST_FAILED, [
                    'job' => class_basename($event->job->resolveName()),
                    'queue' => $event->job->getQueue(),
                    'error' => Str::limit($event->exception->getMessage(), 300),
                    'at' => time(),
                ]);
                self::bump('failed');
            });
        });
    }

    public static function dispatchTestJob(): void
    {
        $token = (string) Str::uuid();

        // Written before dispatching: with the sync driver the job runs
        // inside dispatch() and immediately marks itself processed.
        Cache::forever(self::TEST_JOB, [
            'token' => $token,
            'dispatched_at' => time(),
            'processed_at' => null,
        ]);

        QueueHealthCheckJob::dispatch($token);
    }

    public static function markTestJobProcessed(string $token): void
    {
        $test = Cache::get(self::TEST_JOB);

        // A newer test was sent meanwhile — don't let an old one claim it.
        if (($test['token'] ?? null) !== $token) {
            return;
        }

        $test['processed_at'] = time();
        Cache::forever(self::TEST_JOB, $test);
    }

    public static function snapshot(): array
    {
        $driver = (string) config('queue.default');
        $now = time();

        $workerSeenAt = Cache::get(self::WORKER_SEEN);
        $workerAge = $workerSeenAt ? $now - (int) $workerSeenAt : null;

        $queues = $driver === 'database' ? self::databaseBacklog($now) : [];
        $ready = array_sum(array_column($queues, 'ready'));
        $oldestReadyAt = collect($queues)->pluck('oldest_ready_at')->filter()->min();
        $oldestWait = $oldestReadyAt ? $now - (int) $oldestReadyAt : 0;

        $test = Cache::get(self::TEST_JOB);
        $lastProcessed = Cache::get(self::LAST_PROCESSED);
        $lastFailed = Cache::get(self::LAST_FAILED);
        $today = now()->toDateString();

        return [
            'driver' => $driver,
            'status' => self::status($driver, $workerAge, $ready, $oldestWait),
            'worker_seen_at' => self::time($workerSeenAt),
            'queues' => $queues,
            'ready' => $ready,
            'running' => array_sum(array_column($queues, 'running')),
            'delayed' => array_sum(array_column($queues, 'delayed')),
            'oldest_wait_seconds' => $oldestWait,
            'processed_today' => (int) Cache::get("queue_health:processed:{$today}", 0),
            'failed_today' => (int) Cache::get("queue_health:failed:{$today}", 0),
            'last_processed' => $lastProcessed ? [...$lastProcessed, 'at' => self::time($lastProcessed['at'])] : null,
            'last_failed' => $lastFailed ? [...$lastFailed, 'at' => self::time($lastFailed['at'])] : null,
            'test' => $test ? [
                'dispatched_at' => self::time($test['dispatched_at']),
                'processed_at' => self::time($test['processed_at']),
                'took_seconds' => $test['processed_at'] ? $test['processed_at'] - $test['dispatched_at'] : null,
                'waiting_seconds' => $test['processed_at'] ? null : $now - $test['dispatched_at'],
            ] : null,
            'failed' => self::failedJobs(),
        ];
    }

    /**
     * healthy    — a worker checked in recently and nothing is waiting long
     * slow       — a worker is alive but ready jobs have waited 10+ minutes
     * stuck      — jobs are waiting and no worker has checked in recently
     * idle       — no worker seen recently, but nothing is waiting either
     * unknown    — no worker heartbeat has ever been recorded
     * sync       — QUEUE_CONNECTION=sync, jobs run inline; no worker needed
     */
    private static function status(string $driver, ?int $workerAge, int $ready, int $oldestWait): string
    {
        if ($driver === 'sync') {
            return 'sync';
        }

        if ($workerAge !== null && $workerAge <= self::WORKER_STALE_AFTER_SECONDS) {
            return $oldestWait > self::SLOW_AFTER_SECONDS ? 'slow' : 'healthy';
        }

        if ($ready > 0) {
            return 'stuck';
        }

        return $workerAge === null ? 'unknown' : 'idle';
    }

    /** @return array<int, array{queue: string, ready: int, running: int, delayed: int, oldest_ready_at: ?int}> */
    private static function databaseBacklog(int $now): array
    {
        try {
            return DB::connection(config('queue.connections.database.connection'))
                ->table(config('queue.connections.database.table', 'jobs'))
                ->selectRaw('queue')
                ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at <= ? THEN 1 ELSE 0 END) AS ready_count', [$now])
                ->selectRaw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) AS running_count')
                ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at > ? THEN 1 ELSE 0 END) AS delayed_count', [$now])
                ->selectRaw('MIN(CASE WHEN reserved_at IS NULL AND available_at <= ? THEN available_at END) AS oldest_ready_at', [$now])
                ->groupBy('queue')
                ->orderBy('queue')
                ->get()
                ->map(fn ($row) => [
                    'queue' => (string) $row->queue,
                    'ready' => (int) $row->ready_count,
                    'running' => (int) $row->running_count,
                    'delayed' => (int) $row->delayed_count,
                    'oldest_ready_at' => $row->oldest_ready_at ? (int) $row->oldest_ready_at : null,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array{supported: bool, count: int, recent: array} */
    private static function failedJobs(): array
    {
        if (! str_starts_with((string) config('queue.failed.driver'), 'database')) {
            return ['supported' => false, 'count' => 0, 'recent' => []];
        }

        try {
            $query = DB::connection(config('queue.failed.database'))
                ->table(config('queue.failed.table', 'failed_jobs'));

            $recent = (clone $query)->orderByDesc('id')->limit(10)->get()->map(function ($row) {
                $payload = json_decode($row->payload, true) ?: [];

                return [
                    // queue:retry / queue:forget take the uuid on the
                    // database-uuids driver, the numeric id otherwise.
                    'key' => (string) ($row->uuid ?? $row->id),
                    'job' => class_basename($payload['displayName'] ?? 'Unknown job'),
                    'queue' => $row->queue,
                    'error' => Str::limit(strtok((string) $row->exception, "\n") ?: '', 300),
                    'failed_at' => Carbon::parse($row->failed_at),
                ];
            })->all();

            return ['supported' => true, 'count' => $query->count(), 'recent' => $recent];
        } catch (Throwable) {
            return ['supported' => false, 'count' => 0, 'recent' => []];
        }
    }

    private static function bump(string $type): void
    {
        $key = "queue_health:{$type}:".now()->toDateString();

        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);
    }

    private static function time(mixed $timestamp): ?Carbon
    {
        return $timestamp ? Carbon::createFromTimestamp((int) $timestamp, config('app.timezone')) : null;
    }

    /** Monitoring must never break the job it's watching. */
    private static function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // ignore — a missed heartbeat only makes the monitor less precise
        }
    }
}
