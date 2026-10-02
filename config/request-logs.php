<?php

/*
 * Request log (admin → Visits Url). Every web request — page loads,
 * Livewire actions, AJAX — is written to `request_logs` by
 * App\Http\Middleware\LogRequest, after the response has been sent.
 */
return [

    // Master switch. Off → nothing is written (the admin page still shows
    // whatever was logged before).
    'enabled' => env('REQUEST_LOG_ENABLED', true),

    // Rows older than this are deleted daily (model:prune, routes/console.php).
    'retention_days' => (int) env('REQUEST_LOG_RETENTION_DAYS', 14),

    // A request at or above this many milliseconds counts as "slow".
    'slow_ms' => (int) env('REQUEST_LOG_SLOW_MS', 1000),

];
