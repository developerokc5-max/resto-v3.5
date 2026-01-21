<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class RunPlatformScraper extends Command
{
    protected $signature = 'scrape:platforms
                            {--platform=all : Platform to scrape (grab, foodpanda, deliveroo, all)}
                            {--shop-id= : Specific shop ID to scrape}
                            {--limit=10 : Number of shops to scrape per run}
                            {--headless : Run browser in headless mode}';

    protected $description = 'Run Python-based platform scraper for real data with images';

    public function handle(): int
    {
        $platform = $this->option('platform');
        $shopId = $this->option('shop-id');
        $limit = $this->option('limit');
        $headless = $this->option('headless');

        $this->info("Starting platform scraper...");
        $this->info("Platform: {$platform}");
        $this->info("Limit: {$limit} shops");

        // Build Python command
        $pythonScript = base_path('scrape_platforms.py');

        if (!file_exists($pythonScript)) {
            $this->error("Python scraper not found at: {$pythonScript}");
            return self::FAILURE;
        }

        $command = ['python', $pythonScript];

        $command[] = '--platform';
        $command[] = $platform;

        $command[] = '--limit';
        $command[] = $limit;

        if ($shopId) {
            $command[] = '--shop-id';
            $command[] = $shopId;
        }

        if ($headless) {
            $command[] = '--headless';
        }

        $this->info("\nRunning: " . implode(' ', $command));
        $this->newLine();

        // Execute Python script
        $result = Process::forever()->run($command);

        // Show output
        $this->line($result->output());

        if ($result->failed()) {
            $this->error("Scraper failed!");
            $this->error($result->errorOutput());
            Log::error("Platform scraper failed", [
                'error' => $result->errorOutput()
            ]);
            return self::FAILURE;
        }

        $this->info("\n✅ Platform scraping completed successfully!");

        return self::SUCCESS;
    }
}
