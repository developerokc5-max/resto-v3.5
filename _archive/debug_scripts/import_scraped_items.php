<?php
/**
 * Import Scraped Items from JSON to Database
 * This script processes scraped_items_final.json and imports data into the items table
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\ShopHelper;

echo str_repeat("=", 70) . "\n";
echo "IMPORT SCRAPED ITEMS TO DATABASE\n";
echo str_repeat("=", 70) . "\n\n";

// Get shop map (shop_id => shop info)
$shopMap = ShopHelper::getShopMap();

// Create reverse map (shop_name => shop_id)
$nameToId = [];
foreach ($shopMap as $shopId => $info) {
    $nameToId[$info['name']] = $shopId;
}
echo "[INFO] Loaded " . count($shopMap) . " shop mappings\n";

// Check if JSON file exists
$jsonFile = __DIR__ . '/scraped_items_final.json';

if (!file_exists($jsonFile)) {
    echo "[ERROR] File not found: scraped_items_final.json\n";
    echo "Please run the scraper first: python scrape_items_bulletproof.py\n";
    exit(1);
}

// Load JSON data
echo "Loading JSON data...\n";
$jsonData = file_get_contents($jsonFile);
$items = json_decode($jsonData, true);

if (!$items || !is_array($items)) {
    echo "[ERROR] Invalid JSON format\n";
    exit(1);
}

echo "[OK] Loaded " . count($items) . " items from JSON\n\n";

// Clear existing items
echo "Clearing existing items from database...\n";
DB::table('items')->truncate();
echo "[OK] Database cleared\n\n";

// Import items
echo "Importing items to database...\n";
$imported = 0;
$skipped = 0;
$batchSize = 100;
$batch = [];

foreach ($items as $item) {
    try {
        // Validate required fields
        if (empty($item['store_name']) || empty($item['item_name'])) {
            $skipped++;
            continue;
        }

        // Find shop_id from shop name (trim whitespace)
        $storeName = trim($item['store_name']);
        $shopId = $nameToId[$storeName] ?? null;

        if (!$shopId) {
            // Skip items with unknown shop
            $skipped++;
            continue;
        }

        // Prepare item data
        $itemData = [
            'shop_id' => $shopId,
            'item_id' => $item['item_number'] ?? 0,
            'shop_name' => $storeName,
            'name' => $item['item_name'],
            'sku' => $item['size_name'] ?? null,
            'category' => $item['item_type'] ?? null,
            'price' => is_numeric($item['price'] ?? 0) ? floatval(str_replace(['$', 'S'], '', $item['price'])) : 0,
            'image_url' => $item['image_url'] ?? null,
            'is_available' => ($item['status'] ?? 'UNKNOWN') === 'ONLINE' ? 1 : 0,
            'platform' => strtolower($item['platform']),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $batch[] = $itemData;

        // Insert in batches
        if (count($batch) >= $batchSize) {
            DB::table('items')->insert($batch);
            $imported += count($batch);
            $batch = [];

            // Progress indicator
            if ($imported % 500 == 0) {
                echo "  • Imported {$imported} items...\n";
            }
        }
    } catch (\Exception $e) {
        $skipped++;
        echo "  [WARN] Skipped item: " . ($item['item_name'] ?? 'unknown') . " - " . $e->getMessage() . "\n";
    }
}

// Insert remaining batch
if (count($batch) > 0) {
    DB::table('items')->insert($batch);
    $imported += count($batch);
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "IMPORT COMPLETE\n";
echo str_repeat("=", 70) . "\n";
echo "Total items processed: " . count($items) . "\n";
echo "Successfully imported: {$imported}\n";
echo "Skipped: {$skipped}\n";
echo str_repeat("=", 70) . "\n";

// Show summary stats
$stats = DB::table('items')
    ->selectRaw('
        COUNT(*) as total,
        COUNT(DISTINCT shop_name) as restaurants,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline
    ')
    ->first();

echo "\nDatabase Stats:\n";
echo "  • Total items: {$stats->total}\n";
echo "  • Restaurants: {$stats->restaurants}\n";
echo "  • Available: {$stats->available}\n";
echo "  • Offline: {$stats->offline}\n";
echo "\n[SUCCESS] Import completed successfully!\n";
