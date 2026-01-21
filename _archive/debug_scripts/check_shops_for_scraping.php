<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=" . str_repeat("=", 69) . "\n";
echo "CHECKING SHOPS FOR PLATFORM SCRAPING\n";
echo str_repeat("=", 70) . "\n\n";

// Check if we have shops
$shopsCount = DB::table('shops')->count();
echo "Total shops in database: {$shopsCount}\n\n";

if ($shopsCount > 0) {
    echo "Sample shops:\n";
    $shops = DB::table('shops')
        ->select('shop_id', 'shop_name')
        ->limit(10)
        ->get();

    foreach ($shops as $shop) {
        echo "  [{$shop->shop_id}] {$shop->shop_name}\n";
    }
} else {
    echo "⚠ No shops found in database.\n";
    echo "You need to populate the shops table first.\n\n";

    echo "Checking platform_status table instead...\n";
    $platformShops = DB::table('platform_status')
        ->select('shop_id', 'store_name')
        ->distinct()
        ->limit(10)
        ->get();

    if ($platformShops->count() > 0) {
        echo "\nFound shops in platform_status:\n";
        foreach ($platformShops as $shop) {
            echo "  [{$shop->shop_id}] {$shop->store_name}\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "CHECKING CURRENT ITEMS DATA\n";
echo str_repeat("=", 70) . "\n\n";

$itemsCount = DB::table('items')->count();
echo "Total items in database: {$itemsCount}\n\n";

if ($itemsCount > 0) {
    // Group by platform
    $byPlatform = DB::table('items')
        ->select('platform', DB::raw('COUNT(*) as total'))
        ->groupBy('platform')
        ->get();

    echo "Items by platform:\n";
    foreach ($byPlatform as $row) {
        echo "  {$row->platform}: {$row->total} items\n";
    }

    echo "\n";

    // Offline items by platform
    $offlineByPlatform = DB::table('items')
        ->select('platform', DB::raw('COUNT(*) as total'))
        ->where('is_available', 0)
        ->groupBy('platform')
        ->get();

    echo "Offline items by platform:\n";
    if ($offlineByPlatform->count() > 0) {
        foreach ($offlineByPlatform as $row) {
            echo "  {$row->platform}: {$row->total} items OFFLINE\n";
        }
    } else {
        echo "  No offline items found\n";
    }

    echo "\n";

    // Sample offline items
    $offlineItems = DB::table('items')
        ->where('is_available', 0)
        ->limit(10)
        ->get();

    if ($offlineItems->count() > 0) {
        echo "Sample offline items:\n";
        foreach ($offlineItems as $item) {
            echo "  [{$item->platform}] {$item->item_name} @ Shop {$item->shop_id}\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
