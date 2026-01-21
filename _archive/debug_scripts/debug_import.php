<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\ShopHelper;

// Get shop map
$shopMap = ShopHelper::getShopMap();
$nameToId = [];
foreach ($shopMap as $shopId => $info) {
    $nameToId[$info['name']] = $shopId;
}

// Load JSON data
$jsonData = file_get_contents(__DIR__ . '/scraped_items_final.json');
$items = json_decode($jsonData, true);

echo "Total items in JSON: " . count($items) . "\n\n";

// Count items per store
$storeCount = [];
foreach ($items as $item) {
    $storeName = trim($item['store_name']);
    if (!isset($storeCount[$storeName])) {
        $storeCount[$storeName] = 0;
    }
    $storeCount[$storeName]++;
}

echo "Items per store:\n";
foreach ($storeCount as $store => $count) {
    $shopId = $nameToId[$store] ?? 'NOT FOUND';
    $status = ($shopId !== 'NOT FOUND') ? '✓' : '✗';
    echo "  {$status} {$store}: {$count} items (shop_id: {$shopId})\n";
}

// Show first unmatched item details
echo "\nFirst few items from JSON:\n";
for ($i = 0; $i < 3; $i++) {
    $item = $items[$i];
    $storeName = trim($item['store_name']);
    $shopId = $nameToId[$storeName] ?? 'NOT FOUND';
    echo "\nItem #{$i}:\n";
    echo "  store_name: '{$item['store_name']}'\n";
    echo "  trimmed: '{$storeName}'\n";
    echo "  shop_id: {$shopId}\n";
    echo "  item_name: {$item['item_name']}\n";
    echo "  platform: {$item['platform']}\n";
}
