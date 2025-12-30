<?php

namespace App\Services;

use App\Models\PlatformStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;

/**
 * Platform Scraping Service
 *
 * Scrapes food delivery platforms (Grab, FoodPanda, Deliveroo) to check store status
 * Note: This is a basic implementation. Platforms may require browser automation (Puppeteer/Selenium) for full functionality.
 */
class PlatformScrapingService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for development
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ]
        ]);
    }

    /**
     * Check Grab platform status for a store
     */
    public function checkGrabStatus(string $shopId, string $shopName): array
    {
        try {
            // For now, we'll return mock data
            // TODO: Implement actual Grab scraping when we have store URLs
            Log::info("Checking Grab status for shop: {$shopId}");

            return [
                'platform' => 'grab',
                'shop_id' => $shopId,
                'is_online' => $this->simulateRandomStatus(),
                'items_synced' => rand(50, 150),
                'items_total' => rand(100, 200),
                'last_check_status' => 'success',
                'last_error' => null,
                'store_url' => "https://food.grab.com/sg/en/restaurant/{$shopId}",
            ];
        } catch (\Exception $e) {
            Log::error("Failed to check Grab status for {$shopId}: " . $e->getMessage());

            return [
                'platform' => 'grab',
                'shop_id' => $shopId,
                'is_online' => false,
                'items_synced' => 0,
                'items_total' => 0,
                'last_check_status' => 'error',
                'last_error' => $e->getMessage(),
                'store_url' => null,
            ];
        }
    }

    /**
     * Check FoodPanda platform status for a store
     */
    public function checkFoodPandaStatus(string $shopId, string $shopName): array
    {
        try {
            Log::info("Checking FoodPanda status for shop: {$shopId}");

            return [
                'platform' => 'foodpanda',
                'shop_id' => $shopId,
                'is_online' => $this->simulateRandomStatus(),
                'items_synced' => rand(40, 120),
                'items_total' => rand(80, 180),
                'last_check_status' => 'success',
                'last_error' => null,
                'store_url' => "https://www.foodpanda.sg/restaurant/{$shopId}",
            ];
        } catch (\Exception $e) {
            Log::error("Failed to check FoodPanda status for {$shopId}: " . $e->getMessage());

            return [
                'platform' => 'foodpanda',
                'shop_id' => $shopId,
                'is_online' => false,
                'items_synced' => 0,
                'items_total' => 0,
                'last_check_status' => 'error',
                'last_error' => $e->getMessage(),
                'store_url' => null,
            ];
        }
    }

    /**
     * Check Deliveroo platform status for a store
     */
    public function checkDeliverooStatus(string $shopId, string $shopName): array
    {
        try {
            Log::info("Checking Deliveroo status for shop: {$shopId}");

            return [
                'platform' => 'deliveroo',
                'shop_id' => $shopId,
                'is_online' => $this->simulateRandomStatus(),
                'items_synced' => rand(30, 100),
                'items_total' => rand(70, 150),
                'last_check_status' => 'success',
                'last_error' => null,
                'store_url' => "https://deliveroo.com.sg/menu/singapore/{$shopId}",
            ];
        } catch (\Exception $e) {
            Log::error("Failed to check Deliveroo status for {$shopId}: " . $e->getMessage());

            return [
                'platform' => 'deliveroo',
                'shop_id' => $shopId,
                'is_online' => false,
                'items_synced' => 0,
                'items_total' => 0,
                'last_check_status' => 'error',
                'last_error' => $e->getMessage(),
                'store_url' => null,
            ];
        }
    }

    /**
     * Check all platforms for a store
     */
    public function checkAllPlatforms(string $shopId, string $shopName): array
    {
        return [
            'grab' => $this->checkGrabStatus($shopId, $shopName),
            'foodpanda' => $this->checkFoodPandaStatus($shopId, $shopName),
            'deliveroo' => $this->checkDeliverooStatus($shopId, $shopName),
        ];
    }

    /**
     * Get placeholder/sample images for items
     * In production, this would scrape from actual platform pages
     */
    public function getItemImages(string $shopId): array
    {
        // For now, use placeholder images
        // In production, would scrape from Grab/FoodPanda/Deliveroo
        $sampleImages = [
            'https://images.deliveroo.com/image/upload/c_fill,f_auto,q_auto,w_300,h_300/v1/dishes/singapore/',
            'https://images.grab.com/merchant/dish/',
            'https://images.foodpanda.com/products/',
        ];

        return [
            'image_url' => $sampleImages[array_rand($sampleImages)] . $shopId . '.jpg',
            'thumbnail_url' => null,
        ];
    }

    /**
     * Fetch and parse HTML from a URL
     *
     * @param string $url
     * @return DOMDocument|null
     */
    private function fetchAndParse(string $url): ?DOMDocument
    {
        try {
            $response = $this->client->get($url);
            $html = (string) $response->getBody();

            // Suppress HTML parsing errors
            libxml_use_internal_errors(true);

            $dom = new DOMDocument();
            $dom->loadHTML($html);

            libxml_clear_errors();

            return $dom;
        } catch (GuzzleException $e) {
            Log::error("Failed to fetch {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text content using XPath
     */
    private function extractByXPath(DOMDocument $dom, string $xpath): ?string
    {
        $domXPath = new DOMXPath($dom);
        $nodes = $domXPath->query($xpath);

        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        return null;
    }

    /**
     * Simulate random status for demonstration
     * In production, this will be replaced with actual scraping logic
     */
    private function simulateRandomStatus(): bool
    {
        // 80% chance of being online
        return rand(1, 100) <= 80;
    }

    /**
     * Real implementation example for Grab
     * This is a template - actual selectors need to be found by inspecting Grab's website
     */
    public function checkGrabStatusReal(string $storeUrl): array
    {
        try {
            $dom = $this->fetchAndParse($storeUrl);

            if (!$dom) {
                throw new \Exception("Failed to fetch store page");
            }

            // Example XPath selectors (these need to be updated based on actual Grab HTML structure)
            $isOnline = $this->extractByXPath($dom, "//div[contains(@class, 'store-status')]");
            $itemCount = $this->extractByXPath($dom, "//span[contains(@class, 'item-count')]");

            return [
                'is_online' => stripos($isOnline ?? '', 'open') !== false,
                'items_synced' => (int) ($itemCount ?? 0),
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            return [
                'is_online' => false,
                'items_synced' => 0,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
