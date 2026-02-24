<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Manually registered commands (optional).
     * Laravel will also auto-load commands from app/Console/Commands.
     */
    protected $commands = [
        \App\Console\Commands\TestRestoSuite::class,
        \App\Console\Commands\RestoSuiteSyncItems::class,
        \App\Console\Commands\ScrapePlatformStatus::class,
        \App\Console\Commands\RunPlatformScraper::class,
        \App\Console\Commands\ScrapeRestoSuiteProduction::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Scrapers are run as continuous loops in docker-entrypoint.sh
        // platform-test-trait-1/scrape_platform_sync.py  → loops after each finish
        // item-test-trait-1/scrape_items_sync_v2.py      → loops after each finish
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
