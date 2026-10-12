<?php

namespace App\Jobs\Accounts;

use App\Models\SavedReport;
use App\Services\Accounts\SavedReportRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Runs one saved report on the queue: archive copy + delivery. */
class RunSavedReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $savedReportId) {}

    public function handle(SavedReportRunner $runner): void
    {
        if ($s = SavedReport::find($this->savedReportId)) {
            $runner->run($s);
        }
    }
}
