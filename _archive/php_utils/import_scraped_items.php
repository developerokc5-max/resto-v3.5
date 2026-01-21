<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Bootstrap Laravel database (SQLite)
$capsule = new DB;
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
    'prefix'   => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Starting import of scraped items...\n";

// Load the scraped data
$jsonFile = __DIR__ . '/items_complete.json';
if (!file_exists($jsonFile)) {
    die("ERROR: items_complete.json not found!\n");
}

$data = json_decode(file_get_contents($jsonFile), true);

if (!$data || !isset($data['items'])) {
    die("ERROR: Invalid JSON structure!\n");
}

echo "Found data for " . count($data['items']) . " stores\n";

// Clear existing items table
echo "Clearing existing items...\n";
DB::table('items')->delete();

$totalInserted = 0;
$storeCount = 0;

foreach ($data['items'] as $storeName => $platforms) {
    $storeCount++;
    echo "\n[$storeCount] Processing: $storeName\n";

    foreach ($platforms as $platformName => $items) {
        echo "  Platform: $platformName - " . count($items) . " items\n";

        foreach ($items as $item) {
            try {
                DB::table('items')->insert([
                    'shop_name' => $storeName,
                    'name' => $item['name'],
                    'image_url' => $item['image_url'] ?? null,
                    'category' => $item['category'] ?? '',
                    'sku' => $item['sku'] ?? '',
                    'price' => $item['price'] ?? 0,
                    'platform' => strtolower($platformName),
                    'is_available' => $item['is_available'] ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $totalInserted++;
            } catch (Exception $e) {
                echo "    ERROR inserting item: " . $item['name'] . " - " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "IMPORT COMPLETE!\n";
echo "Total items inserted: $totalInserted\n";
echo "Total stores processed: $storeCount\n";
echo str_repeat("=", 70) . "\n";

// Show summary stats
$stats = DB::table('items')
    ->select(
        DB::raw('COUNT(*) as total'),
        DB::raw('COUNT(DISTINCT shop_name) as stores'),
        DB::raw('COUNT(DISTINCT (shop_name || "|" || name)) as unique_items'),
        DB::raw('SUM(is_available) as available')
    )
    ->first();

echo "\nDatabase Statistics:\n";
echo "  Total records: {$stats->total}\n";
echo "  Unique items: {$stats->unique_items}\n";
echo "  Stores: {$stats->stores}\n";
echo "  Available: {$stats->available}\n";

echo "\nItems page is now ready with real-time data!\n";
echo "Visit: http://127.0.0.1:8000/items\n";
