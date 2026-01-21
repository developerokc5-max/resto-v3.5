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
        // API Sync - Every 5 minutes
        $schedule->command('restosuite:sync-items --page=1 --size=100')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // HYBRID: Platform Scraping - Every 10 minutes (offset by 2 minutes to avoid collision)
        // Scrapes 15 shops per run to distribute load
        $schedule->command('scrape:platform-status --limit=15')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Platform scraping failed');
            })
            ->onSuccess(function () {
                \Log::info('Platform scraping completed successfully');
            });

        // PRODUCTION: Real Platform Scraper with Browser Automation - Every 30 minutes
        // Gets REAL images, prices, and availability from Grab/FoodPanda/Deliveroo
        $schedule->command('scrape:platforms --platform=all --limit=5 --headless')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Platform browser scraping failed');
            })
            ->onSuccess(function () {
                \Log::info('Platform browser scraping completed successfully');
            });
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
