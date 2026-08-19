<?php

namespace App\Console\Commands;

use App\Models\DeviceVisit;
use Illuminate\Console\Command;

class PruneDeviceVisits extends Command
{
    protected $signature = 'devices:prune-visits {--days=90 : Delete visit records older than this many days}';

    protected $description = 'Delete old device_visits records to keep the table from growing unbounded';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = DeviceVisit::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} device visit " . ($deleted === 1 ? 'record' : 'records') . " older than {$days} days.");

        return self::SUCCESS;
    }
}
