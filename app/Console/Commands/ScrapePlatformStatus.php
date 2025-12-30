<?php

namespace App\Console\Commands;

use App\Helpers\ShopHelper;
use App\Services\PlatformScrapingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapePlatformStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scrape:platform-status
                            {--platform= : Specific platform to scrape (grab, foodpanda, deliveroo)}
                            {--shop= : Specific shop ID to scrape}
                            {--limit=10 : Number of shops to scrape per run}';

    /**
     * The console command description.
     */
    protected $description = 'Scrape platform status for stores (Grab, FoodPanda, Deliveroo)';

    private PlatformScrapingService $scraper;

    public function __construct(PlatformScrapingService $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting platform status scraping...');

        $platform = $this->option('platform');
        $shopId = $this->option('shop');
        $limit = (int) $this->option('limit');

        try {
            // Get shop list
            $shopMap = ShopHelper::getShopMap();

            // Filter out testing shops
            $testingShopIds = [];
            foreach ($shopMap as $id => $info) {
                if (stripos($info['name'], 'testing') !== false ||
                    stripos($info['name'], 'office testing') !== false) {
                    $testingShopIds[] = $id;
                }
            }

            // Get shops to scrape
            $shopsToScrape = [];
            if ($shopId) {
                // Specific shop
                if (isset($shopMap[$shopId])) {
                    $shopsToScrape[$shopId] = $shopMap[$shopId];
                } else {
                    $this->error("Shop ID {$shopId} not found");
                    return self::FAILURE;
                }
            } else {
                // All production shops (excluding testing)
                foreach ($shopMap as $id => $info) {
                    if (!in_array($id, $testingShopIds)) {
                        $shopsToScrape[$id] = $info;
                    }
                }
            }

            // Limit the number of shops per run
            $shopsToScrape = array_slice($shopsToScrape, 0, $limit, true);

            $this->info("Found " . count($shopsToScrape) . " shops to scrape");

            $scraped = 0;
            $errors = 0;

            // Progress bar
            $bar = $this->output->createProgressBar(count($shopsToScrape));
            $bar->start();

            foreach ($shopsToScrape as $id => $info) {
                try {
                    $this->scrapeShop($id, $info, $platform);
                    $scraped++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Failed to scrape shop {$id}: " . $e->getMessage());
                }

                $bar->advance();

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Scraping completed!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Shops Scraped', $scraped],
                    ['Errors', $errors],
                    ['Success Rate', round(($scraped / count($shopsToScrape)) * 100, 2) . '%'],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Scraping failed: " . $e->getMessage());
            Log::error("Platform scraping error: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Scrape a single shop
     */
    private function scrapeShop(string $shopId, array $shopInfo, ?string $platformFilter): void
    {
        $shopName = $shopInfo['name'] ?? 'Unknown';

        // Determine which platforms to scrape
        $platforms = $platformFilter ? [$platformFilter] : ['grab', 'foodpanda', 'deliveroo'];

        foreach ($platforms as $platform) {
            try {
                // Get platform status
                $status = match ($platform) {
                    'grab' => $this->scraper->checkGrabStatus($shopId, $shopName),
                    'foodpanda' => $this->scraper->checkFoodPandaStatus($shopId, $shopName),
                    'deliveroo' => $this->scraper->checkDeliverooStatus($shopId, $shopName),
                    default => throw new \Exception("Unknown platform: {$platform}"),
                };

                // Save to database
                DB::table('platform_status')->updateOrInsert(
                    [
                        'shop_id' => $shopId,
                        'platform' => $platform,
                    ],
                    [
                        'is_online' => $status['is_online'],
                        'items_synced' => $status['items_synced'] ?? 0,
                        'items_total' => $status['items_total'] ?? 0,
                        'store_name' => $shopName,
                        'store_url' => $status['store_url'] ?? null,
                        'last_checked_at' => now(),
                        'last_check_status' => $status['last_check_status'],
                        'last_error' => $status['last_error'],
                        'updated_at' => now(),
                    ]
                );

                // Detect status changes and log them
                $this->detectStatusChange($shopId, $platform, $status['is_online'], $shopName);
            } catch (\Exception $e) {
                Log::error("Failed to scrape {$platform} for shop {$shopId}: " . $e->getMessage());

                // Save error to database
                DB::table('platform_status')->updateOrInsert(
                    [
                        'shop_id' => $shopId,
                        'platform' => $platform,
                    ],
                    [
                        'is_online' => false,
                        'store_name' => $shopName,
                        'last_checked_at' => now(),
                        'last_check_status' => 'error',
                        'last_error' => $e->getMessage(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Detect and log status changes
     */
    private function detectStatusChange(string $shopId, string $platform, bool $isOnline, string $shopName): void
    {
        $previous = DB::table('platform_status')
            ->where('shop_id', $shopId)
            ->where('platform', $platform)
            ->first();

        if ($previous && $previous->is_online != $isOnline) {
            $status = $isOnline ? 'ONLINE' : 'OFFLINE';
            $emoji = $isOnline ? '✅' : '❌';

            Log::info("Platform status change detected: {$shopName} on {$platform} is now {$status}");

            // You can add notification logic here (Telegram, email, etc.)
            // For now, just log it
        }
    }
}
