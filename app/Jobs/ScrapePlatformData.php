<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ScrapePlatformData implements ShouldQueue
{
    use Queueable;

    public $timeout = 180; // 3 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting platform data scraping job...');

        try {
            // Run the Python scraper
            $scriptPath = base_path('scrape_platform_status.py');
            $outputFile = storage_path('app/scraper_output.json');

            $command = "python \"{$scriptPath}\" 2>&1";

            exec($command, $output, $returnCode);

            $rawOutput = implode("\n", $output);

            if ($returnCode !== 0) {
                Log::error('Scraper failed', ['return_code' => $returnCode, 'output' => $rawOutput]);
                return;
            }

            // Extract JSON from output
            if (preg_match('/(\{.*\})/s', $rawOutput, $matches)) {
                $jsonOutput = $matches[1];
            } else {
                Log::error('No JSON found in scraper output', ['output' => $rawOutput]);
                return;
            }

            $data = json_decode($jsonOutput, true);

            if (!$data) {
                Log::error('Failed to decode JSON', ['json' => $jsonOutput]);
                return;
            }

            // Save to cache file
            $cacheFile = storage_path('app/platform_data_cache.json');
            file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));

            Log::info('Platform data cached successfully', [
                'grab' => count($data['grab'] ?? []),
                'deliveroo' => count($data['deliveroo'] ?? []),
                'foodpanda' => count($data['foodpanda'] ?? []),
            ]);

            // Update database
            \Artisan::call('scrape:platform-status');

            Log::info('Platform scraping job completed successfully');

        } catch (\Exception $e) {
            Log::error('Platform scraping job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
