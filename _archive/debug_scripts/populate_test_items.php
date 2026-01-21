<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Populating Test Items from Web Scraping ===\n\n";

// Get shops from RestoSuite API
$shopIds = DB::table('restosuite_item_snapshots')
    ->select('shop_id')
    ->distinct()
    ->pluck('shop_id');

echo "Found " . count($shopIds) . " shops\n";

// Get shop names from helper
use App\Helpers\ShopHelper;
$shopMap = ShopHelper::getShopMap();

// Get items from snapshots and create platform variants
$platforms = ['grab', 'foodpanda', 'deliveroo'];
$totalInserted = 0;

foreach ($shopIds as $shopId) {
    $shopName = $shopMap[$shopId]['name'] ?? 'Unknown Shop';
    echo "Processing: {$shopName} ({$shopId})\n";

    // Get items for this shop
    $items = DB::table('restosuite_item_snapshots')
        ->where('shop_id', $shopId)
        ->select('name', 'price', 'image_url', 'raw_json')
        ->distinct()
        ->get();

    $shopInserted = 0;

    foreach ($items as $item) {
        // Parse category from raw_json
        $category = 'General';
        if ($item->raw_json) {
            $data = json_decode($item->raw_json, true);
            if (isset($data['category']['categoryName'])) {
                $category = $data['category']['categoryName'];
            }
        }

        // Randomly select 1-3 platforms this item appears on
        $numPlatforms = rand(1, 3);
        $selectedPlatforms = array_rand(array_flip($platforms), $numPlatforms);
        if (!is_array($selectedPlatforms)) {
            $selectedPlatforms = [$selectedPlatforms];
        }

        foreach ($selectedPlatforms as $platform) {
            // Random availability (80% chance of being available)
            $isAvailable = rand(1, 100) <= 80;

            // Price might vary slightly by platform (±10%)
            $basePrice = $item->price ?: 5.00;
            $platformPrice = $basePrice * (rand(90, 110) / 100);

            DB::table('items')->insert([
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'item_id' => null,
                'name' => $item->name,
                'sku' => null,
                'category' => $category,
                'price' => round($platformPrice, 2),
                'is_available' => $isAvailable,
                'image_url' => $item->image_url,
                'platform' => $platform,
                'platform_item_id' => uniqid($platform . '_'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $shopInserted++;
            $totalInserted++;

            // Also insert into history for tracking
            DB::table('item_status_history')->insert([
                'item_name' => $item->name,
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'platform' => $platform,
                'is_available' => $isAvailable,
                'price' => round($platformPrice, 2),
                'category' => $category,
                'image_url' => $item->image_url,
                'changed_at' => now()->subMinutes(rand(1, 60)), // Random time in last hour
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    echo "  → Inserted {$shopInserted} items\n";
}

echo "\n✅ Done! Total items inserted: {$totalInserted}\n";
echo "   Check /items page to see the data\n";
