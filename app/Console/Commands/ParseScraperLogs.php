<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ParseScraperLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse-scraper-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse actual scraper log files and store in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Parse items scraper log
        $this->parseItemsLog();

        // Parse platform scraper log
        $this->parsePlatformLog();

        $this->info('✓ Scraper logs parsed and stored successfully!');
    }

    private function parseItemsLog()
    {
        $logFile = 'C:\resto-db-v3.5\item-test-trait-1\scrape_items_sync_v2.log';

        if (!file_exists($logFile)) {
            $this->warn("Items log file not found: $logFile");
            return;
        }

        $lines = file($logFile);
        $itemsProcessed = 0;
        $itemsUpdated = 0;
        $status = 'success';
        $lastExecutedAt = null;

        foreach ($lines as $line) {
            // Parse log lines to extract metrics
            if (strpos($line, 'Total time') !== false) {
                // Extract total items
                preg_match('/(\d+)\s+items/', $line, $matches);
                if (isset($matches[1])) {
                    $itemsProcessed = (int)$matches[1];
                }
                // Extract timestamp
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
                if (isset($matches[1])) {
                    $lastExecutedAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
                }
            }
            if (strpos($line, 'Worker') !== false && strpos($line, 'completed') !== false) {
                preg_match('/(\d+)\s+items/', $line, $matches);
                if (isset($matches[1])) {
                    $itemsUpdated += (int)$matches[1];
                }
            }
        }

        if ($lastExecutedAt) {
            \DB::table('scraper_logs')->insert([
                'scraper_name' => 'items',
                'status' => $status,
                'items_processed' => $itemsProcessed,
                'items_updated' => $itemsUpdated,
                'log_message' => 'Items scraper execution',
                'executed_at' => $lastExecutedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✓ Parsed items log: $itemsProcessed items processed, $itemsUpdated updated");
        }
    }

    private function parsePlatformLog()
    {
        $logFile = 'C:\resto-db-v3.5\platform-test-trait-1\scrape_platform_sync.log';

        if (!file_exists($logFile)) {
            $this->warn("Platform log file not found: $logFile");
            return;
        }

        $lines = file($logFile);
        $storesChecked = 0;
        $status = 'success';
        $lastExecutedAt = null;

        foreach ($lines as $line) {
            // Parse log lines
            if (strpos($line, 'Step') !== false || strpos($line, 'completed') !== false) {
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches);
                if (isset($matches[1])) {
                    $lastExecutedAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
                }
                $storesChecked++;
            }
        }

        if ($lastExecutedAt) {
            \DB::table('scraper_logs')->insert([
                'scraper_name' => 'platform',
                'status' => $status,
                'items_processed' => $storesChecked,
                'items_updated' => $storesChecked,
                'log_message' => 'Platform scraper execution',
                'executed_at' => $lastExecutedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✓ Parsed platform log: $storesChecked stores checked");
        }
    }
}
