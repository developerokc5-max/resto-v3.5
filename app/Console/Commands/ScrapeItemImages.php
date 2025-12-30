<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Helpers\ShopHelper;

class ScrapeItemImages extends Command
{
    protected $signature = 'scrape:item-images
        {--shopId= : Optional single shopId to scrape}
        {--platform=grab : Platform to scrape from (grab, foodpanda, deliveroo)}
        {--limit=10 : Limit number of shops to process}';

    protected $description = 'Scrape item images from delivery platforms (Grab, FoodPanda, Deliveroo)';

    private array $platformUrls = [
        'grab' => [
            'base' => 'https://portal.grab.com/foodweb/v2/search',
            'menu' => 'https://portal.grab.com/foodweb/v2/merchant-menu',
        ],
        'foodpanda' => [
            'base' => 'https://www.foodpanda.sg/restaurant',
        ],
        'deliveroo' => [
            'base' => 'https://deliveroo.com.sg/menu/singapore',
        ],
    ];

    public function handle(): int
    {
        $shopId = $this->option('shopId');
        $platform = $this->option('platform') ?? 'grab';
        $limit = (int) $this->option('limit');

        $this->info("Starting image scraping from {$platform}...");

        $shopMap = ShopHelper::getShopMap();

        // Get shops that need image scraping
        $shopsToScrape = [];
        if ($shopId) {
            $shopsToScrape = [$shopId => $shopMap[$shopId] ?? null];
        } else {
            // Get shops with items that don't have images
            $shopsNeedingImages = DB::table('restosuite_item_snapshots')
                ->select('shop_id', DB::raw('COUNT(*) as items_without_images'))
                ->whereNull('image_url')
                ->groupBy('shop_id')
                ->orderByDesc('items_without_images')
                ->limit($limit)
                ->get();

            foreach ($shopsNeedingImages as $shop) {
                if (isset($shopMap[$shop->shop_id])) {
                    $shopsToScrape[$shop->shop_id] = $shopMap[$shop->shop_id];
                }
            }
        }

        $this->info("Found " . count($shopsToScrape) . " shops to scrape");

        $totalUpdated = 0;
        $totalItems = 0;

        foreach ($shopsToScrape as $shopId => $shopInfo) {
            if (!$shopInfo) continue;

            $this->line("→ Scraping: {$shopInfo['name']} ({$shopId})");

            // Get platform URLs for this shop
            $platformStatus = DB::table('platform_status')
                ->where('shop_id', $shopId)
                ->where('platform', $platform)
                ->first();

            if (!$platformStatus || !$platformStatus->store_url) {
                $this->warn("  No {$platform} URL found for this shop, skipping...");
                continue;
            }

            // Scrape images based on platform
            $images = match($platform) {
                'grab' => $this->scrapeGrabImages($platformStatus->store_url, $shopId),
                'foodpanda' => $this->scrapeFoodpandaImages($platformStatus->store_url, $shopId),
                'deliveroo' => $this->scrapeDeliverooImages($platformStatus->store_url, $shopId),
                default => [],
            };

            if (empty($images)) {
                $this->warn("  No images found");
                continue;
            }

            $this->info("  Found " . count($images) . " items with images");
            $totalItems += count($images);

            // Update database with images
            foreach ($images as $itemName => $imageUrl) {
                $updated = DB::table('restosuite_item_snapshots')
                    ->where('shop_id', $shopId)
                    ->where('name', 'LIKE', '%' . $itemName . '%')
                    ->whereNull('image_url')
                    ->update(['image_url' => $imageUrl]);

                if ($updated > 0) {
                    $totalUpdated++;
                }
            }
        }

        $this->info("Done! Updated {$totalUpdated} items with images (from {$totalItems} scraped)");

        return self::SUCCESS;
    }

    private function scrapeGrabImages(string $url, string $shopId): array
    {
        $images = [];

        try {
            $this->line("  Scraping Grab page: {$url}");

            // Scrape the HTML page
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->get($url);

            if (!$response->successful()) {
                $this->warn("  Failed to load page (HTTP {$response->status()})");
                return $images;
            }

            $html = $response->body();

            // Look for Next.js data or React props that contain menu info
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                // Try to find menu items in the Next.js data
                $this->extractGrabItemsFromData($jsonData, $images);
            }

            // Also try to find images directly in HTML
            preg_match_all('/"photoURL":"(https:\/\/[^"]+)"[^}]*"name":"([^"]+)"/i', $html, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (isset($match[1]) && isset($match[2])) {
                    $cleanName = $this->cleanItemName($match[2]);
                    $images[$cleanName] = $match[1];
                    $this->line("    ✓ {$match[2]}");
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error scraping Grab: " . $e->getMessage());
        }

        return $images;
    }

    private function extractGrabItemsFromData(array $data, array &$images): void
    {
        // Recursively search for menu items in the data structure
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Check if this looks like a menu item
                if (isset($value['name']) && isset($value['photoURL'])) {
                    $cleanName = $this->cleanItemName($value['name']);
                    $images[$cleanName] = $value['photoURL'];
                    $this->line("    ✓ {$value['name']}");
                } else {
                    // Recurse deeper
                    $this->extractGrabItemsFromData($value, $images);
                }
            }
        }
    }

    private function scrapeFoodpandaImages(string $url, string $shopId): array
    {
        $images = [];

        try {
            // FoodPanda requires more complex scraping or their API
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return $images;
            }

            // Parse HTML for structured data
            $html = $response->body();

            // Look for JSON-LD structured data
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                if (isset($jsonData['hasMenu']['hasMenuSection'])) {
                    foreach ($jsonData['hasMenu']['hasMenuSection'] as $section) {
                        if (isset($section['hasMenuItem'])) {
                            foreach ($section['hasMenuItem'] as $item) {
                                $name = $item['name'] ?? '';
                                $imageUrl = $item['image'] ?? '';

                                if ($name && $imageUrl) {
                                    $cleanName = $this->cleanItemName($name);
                                    $images[$cleanName] = $imageUrl;
                                }
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error scraping FoodPanda: " . $e->getMessage());
        }

        return $images;
    }

    private function scrapeDeliverooImages(string $url, string $shopId): array
    {
        $images = [];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return $images;
            }

            $html = $response->body();

            // Deliveroo uses React/Next.js with data in __NEXT_DATA__
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                // Navigate through the data structure to find menu items
                if (isset($jsonData['props']['initialState']['menu'])) {
                    $menuData = $jsonData['props']['initialState']['menu'];

                    foreach ($menuData as $section) {
                        if (isset($section['items'])) {
                            foreach ($section['items'] as $item) {
                                $name = $item['name'] ?? '';
                                $imageUrl = $item['image_url'] ?? '';

                                if ($name && $imageUrl) {
                                    $cleanName = $this->cleanItemName($name);
                                    $images[$cleanName] = $imageUrl;
                                }
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error scraping Deliveroo: " . $e->getMessage());
        }

        return $images;
    }

    private function cleanItemName(string $name): string
    {
        // Remove special characters, extra spaces, and normalize
        $name = preg_replace('/[^\w\s]/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
}
