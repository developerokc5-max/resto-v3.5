<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class ScrapeRestoSuiteProduction extends Command
{
    protected $signature = 'scrape:restosuite-production
                            {--headless : Run browser in headless mode}';

    protected $description = 'Scrape ALL RestoSuite stores with images and full data';

    public function handle(): int
    {
        $this->info("╔" . str_repeat("═", 78) . "╗");
        $this->info("║" . str_pad(" RestoSuite Production Scraper ", 78, " ", STR_PAD_BOTH) . "║");
        $this->info("╚" . str_repeat("═", 78) . "╝");
        $this->newLine();

        $pythonScript = base_path('scrape_restosuite_production.py');

        if (!file_exists($pythonScript)) {
            $this->error("Python scraper not found at: {$pythonScript}");
            return self::FAILURE;
        }

        $this->info("📍 Script: {$pythonScript}");
        $this->info("🎯 Target: ALL stores (not just 3)");
        $this->info("💾 Saves: Images, prices, SKU, categories");
        $this->info("⏭️  Skips: Unbound stores (no items)");
        $this->newLine();

        $command = ['python', $pythonScript];

        $this->warn("⚠️  This will take several minutes to complete...");
        $this->newLine();

        // Execute Python script with real-time output
        $result = Process::forever()->run($command, function ($type, $output) {
            // Stream output in real-time
            if ($type === 'err') {
                // stderr contains the log output
                $this->line($output);
            } else {
                $this->line($output);
            }
        });

        if ($result->failed()) {
            $this->error("\n✗ Scraper failed!");
            $this->error($result->errorOutput());
            Log::error("RestoSuite production scraper failed", [
                'error' => $result->errorOutput()
            ]);
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Scraping completed successfully!");

        // Show results summary if available
        $resultsFile = base_path('scrape_results.json');
        if (file_exists($resultsFile)) {
            $results = json_decode(file_get_contents($resultsFile), true);

            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Stores Found', $results['total_stores_found'] ?? 0],
                    ['Stores Scraped', $results['stores_scraped'] ?? 0],
                    ['Stores Skipped', $results['stores_skipped'] ?? 0],
                    ['Total Items', $results['total_items'] ?? 0],
                    ['Items Inserted', $results['items_inserted'] ?? 0],
                    ['Items Updated', $results['items_updated'] ?? 0],
                    ['History Records', $results['history_records'] ?? 0],
                    ['Errors', count($results['errors'] ?? [])],
                ]
            );
        }

        $this->newLine();
        $this->info("🎉 Run report to see updated data:");
        $this->info("   php report_platform_items.php");

        return self::SUCCESS;
    }
}
