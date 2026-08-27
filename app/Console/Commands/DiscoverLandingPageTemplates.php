<?php

namespace App\Console\Commands;

use App\LandingPageEngine\TemplateDiscoveryService;
use Illuminate\Console\Command;

class DiscoverLandingPageTemplates extends Command
{
    protected $signature = 'landingpage:discover-templates';

    protected $description = 'Scan resources/landingpage-templates and storage/landingpage-templates and register/refresh them in the database';

    public function handle(TemplateDiscoveryService $service): int
    {
        $result = $service->discover();

        foreach ($result['registered'] as $line) {
            $this->info("Registered: {$line}");
        }

        foreach ($result['skipped'] as $line) {
            $this->warn("Skipped: {$line}");
        }

        $this->info(count($result['registered']) . ' template(s) registered, ' . count($result['skipped']) . ' skipped.');

        return self::SUCCESS;
    }
}
