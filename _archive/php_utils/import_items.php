#!/usr/bin/env php
<?php
/**
 * Import REAL Scraped Items to Database
 *
 * Usage: php import_items.php
 * Imports items from items_scraped.json into the database
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Load REAL scraped items data
$jsonPath = 'items_scraped.json';

if (!file_exists($jsonPath)) {
    die("ERROR: items_scraped.json not found! Make sure the scraper has completed.\n");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);

if (!isset($data['items']) || empty($data['items'])) {
    die("ERROR: No items found in items_scraped.json\n");
}

$items = $data['items'];

echo "============================================================\n";
echo "IMPORTING REAL SCRAPED ITEMS\n";
echo "============================================================\n";
echo "Total items to import: " . count($items) . "\n";
echo "Unique stores: " . count(array_unique(array_column($items, 'shop_name'))) . "\n";
echo "\n";

// Clear existing items table
echo "Clearing existing items...\n";
DB::table('items')->truncate();

$imported = 0;
$skipped = 0;

foreach ($items as $item) {
    try {
        // Validate required fields
        if (empty($item['name']) || empty($item['shop_name']) || empty($item['platform'])) {
            echo "⚠ Skipping item with missing required fields\n";
            $skipped++;
            continue;
        }

        DB::table('items')->insert([
            'item_id' => $item['item_id'] ?? null,
            'shop_name' => $item['shop_name'],
            'name' => $item['name'],
            'sku' => $item['sku'] ?? $item['item_id'] ?? null,
            'category' => $item['category'] ?? 'Uncategorized',
            'price' => $item['price'] ?? 0,
            'image_url' => $item['image_url'] ?? null,
            'is_available' => $item['is_available'] ?? true,
            'platform' => $item['platform'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imported++;

        // Show progress every 50 items
        if ($imported % 50 == 0) {
            echo "✓ Imported $imported items...\n";
        }
    } catch (\Exception $e) {
        echo "⚠ Error importing item: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

echo "\n============================================================\n";
echo "IMPORT COMPLETE\n";
echo "============================================================\n";
echo "✓ Successfully imported: $imported items\n";
if ($skipped > 0) {
    echo "⚠ Skipped: $skipped items\n";
}

// Show summary by store
echo "\nItems by store:\n";
$storeStats = DB::table('items')
    ->select('shop_name', DB::raw('COUNT(*) as count'))
    ->groupBy('shop_name')
    ->orderBy('count', 'desc')
    ->get();

foreach ($storeStats as $stat) {
    echo "  • {$stat->shop_name}: {$stat->count} items\n";
}

echo "\nItems by platform:\n";
$platformStats = DB::table('items')
    ->select('platform', DB::raw('COUNT(*) as count'))
    ->groupBy('platform')
    ->get();

foreach ($platformStats as $stat) {
    echo "  • {$stat->platform}: {$stat->count} items\n";
}

echo "\n✅ Ready to view at: http://localhost:8000/items\n";
