<?php

namespace App\Console\Commands;

use App\Helpers\ShopHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapePlatformItems extends Command
{
    protected $signature = 'scrape:platform-items
                            {--platform=grab : Platform to scrape (grab, foodpanda, deliveroo)}
                            {--shop= : Specific shop ID to scrape}
                            {--limit=10 : Number of shops to scrape per run}';

    protected $description = 'Scrape item availability from delivery platforms and track changes';

    public function handle(): int
    {
        $platform = $this->option('platform');
        $shopId = $this->option('shop');
        $limit = (int) $this->option('limit');

        $runId = now()->toDateTimeString();

        $this->info("Starting {$platform} item scraping (run: {$runId})...");

        // Get shop list
        $shopMap = ShopHelper::getShopMap();

        // Filter testing shops
        $testingShopIds = [];
        foreach ($shopMap as $id => $info) {
            if (stripos($info['name'], 'testing') !== false) {
                $testingShopIds[] = $id;
            }
        }

        // Get shops to scrape
        $shopsToScrape = [];
        if ($shopId) {
            if (isset($shopMap[$shopId])) {
                $shopsToScrape[$shopId] = $shopMap[$shopId];
            } else {
                $this->error("Shop ID {$shopId} not found");
                return self::FAILURE;
            }
        } else {
            // All production shops
            foreach ($shopMap as $id => $info) {
                if (!in_array($id, $testingShopIds)) {
                    $shopsToScrape[$id] = $info;
                }
            }
        }

        $shopsToScrape = array_slice($shopsToScrape, 0, $limit, true);
        $this->info("Found " . count($shopsToScrape) . " shops to scrape");

        $totalItems = 0;
        $itemsInserted = 0;
        $historyInserted = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar(count($shopsToScrape));
        $bar->start();

        foreach ($shopsToScrape as $id => $info) {
            try {
                $result = $this->scrapeShopItems($id, $info, $platform, $runId);
                $totalItems += $result['items_found'];
                $itemsInserted += $result['items_inserted'];
                $historyInserted += $result['history_inserted'];
            } catch (\Exception $e) {
                $errors++;
                Log::error("Failed to scrape shop {$id}: " . $e->getMessage());
            }

            $bar->advance();
            usleep(500000); // 0.5 second delay
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Scraping completed!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Items Found', $totalItems],
                ['Items Inserted/Updated', $itemsInserted],
                ['History Records Created', $historyInserted],
                ['Errors', $errors],
            ]
        );

        return self::SUCCESS;
    }

    private function scrapeShopItems(string $shopId, array $shopInfo, string $platform, string $runId): array
    {
        $shopName = $shopInfo['name'] ?? 'Unknown';

        // Get items based on platform
        $items = match($platform) {
            'grab' => $this->scrapeGrabItems($shopId, $shopName),
            'foodpanda' => $this->scrapeFoodpandaItems($shopId, $shopName),
            'deliveroo' => $this->scrapeDeliverooItems($shopId, $shopName),
            default => [],
        };

        $itemsInserted = 0;
        $historyInserted = 0;

        foreach ($items as $item) {
            // Upsert into items table
            DB::table('items')->updateOrInsert(
                [
                    'shop_id' => $shopId,
                    'platform' => $platform,
                    'item_name' => $item['name'],
                ],
                [
                    'is_available' => $item['is_available'],
                    'price' => $item['price'] ?? null,
                    'category' => $item['category'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'platform_item_id' => $item['platform_id'] ?? null,
                    'updated_at' => now(),
                ]
            );
            $itemsInserted++;

            // Check if availability changed
            $previous = DB::table('item_status_history')
                ->where('shop_id', $shopId)
                ->where('platform', $platform)
                ->where('item_name', $item['name'])
                ->orderByDesc('changed_at')
                ->first();

            // Insert history if new item or status changed
            if (!$previous || $previous->is_available != $item['is_available']) {
                DB::table('item_status_history')->insert([
                    'item_name' => $item['name'],
                    'shop_id' => $shopId,
                    'shop_name' => $shopName,
                    'platform' => $platform,
                    'is_available' => $item['is_available'],
                    'price' => $item['price'] ?? null,
                    'category' => $item['category'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $historyInserted++;
            }
        }

        return [
            'items_found' => count($items),
            'items_inserted' => $itemsInserted,
            'history_inserted' => $historyInserted,
        ];
    }

    private function scrapeGrabItems(string $shopId, string $shopName): array
    {
        $items = [];

        try {
            $url = "https://portal.grab.com/foodweb/v2/merchant-menu?merchantID={$shopId}";

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Parse Grab menu structure
                if (isset($data['menu']['categories'])) {
                    foreach ($data['menu']['categories'] as $category) {
                        $categoryName = $category['name'] ?? '';

                        if (isset($category['items'])) {
                            foreach ($category['items'] as $item) {
                                $items[] = [
                                    'name' => $item['name'] ?? '',
                                    'platform_id' => $item['id'] ?? '',
                                    'is_available' => $item['available'] ?? true,
                                    'price' => isset($item['price']) ? ($item['price'] / 100) : null, // Convert cents
                                    'category' => $categoryName,
                                    'image_url' => $item['photoURL'] ?? null,
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to scrape Grab items for {$shopId}: " . $e->getMessage());
        }

        return $items;
    }

    private function scrapeFoodpandaItems(string $shopId, string $shopName): array
    {
        $items = [];

        try {
            // FoodPanda API endpoint (may need adjustment)
            $url = "https://www.foodpanda.sg/restaurant/{$shopId}";

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Look for structured JSON data
                if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                    $data = json_decode($matches[1], true);

                    // Navigate to menu items (structure may vary)
                    if (isset($data['props']['initialState']['menu']['sections'])) {
                        foreach ($data['props']['initialState']['menu']['sections'] as $section) {
                            $categoryName = $section['name'] ?? '';

                            if (isset($section['products'])) {
                                foreach ($section['products'] as $product) {
                                    $items[] = [
                                        'name' => $product['name'] ?? '',
                                        'platform_id' => $product['id'] ?? '',
                                        'is_available' => $product['available'] ?? true,
                                        'price' => $product['price'] ?? null,
                                        'category' => $categoryName,
                                        'image_url' => $product['image'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to scrape FoodPanda items for {$shopId}: " . $e->getMessage());
        }

        return $items;
    }

    private function scrapeDeliverooItems(string $shopId, string $shopName): array
    {
        $items = [];

        try {
            $url = "https://deliveroo.com.sg/menu/singapore/{$shopId}";

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                    $data = json_decode($matches[1], true);

                    // Navigate menu structure (varies by Deliveroo version)
                    if (isset($data['props']['initialState']['menu']['sections'])) {
                        foreach ($data['props']['initialState']['menu']['sections'] as $section) {
                            $categoryName = $section['name'] ?? '';

                            if (isset($section['items'])) {
                                foreach ($section['items'] as $item) {
                                    $items[] = [
                                        'name' => $item['name'] ?? '',
                                        'platform_id' => $item['id'] ?? '',
                                        'is_available' => $item['available'] ?? true,
                                        'price' => $item['raw_price'] ?? null,
                                        'category' => $categoryName,
                                        'image_url' => $item['image_url'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to scrape Deliveroo items for {$shopId}: " . $e->getMessage());
        }

        return $items;
    }
}
