<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\ShopHelper;

// Get shop names from scraped JSON
$jsonData = file_get_contents(__DIR__ . '/scraped_items_final.json');
$items = json_decode($jsonData, true);
$scrapedShops = array_unique(array_column($items, 'store_name'));
sort($scrapedShops);

// Get shop names from ShopHelper
$shopMap = ShopHelper::getShopMap();
$helperShops = array_column($shopMap, 'name');
sort($helperShops);

echo "======================================\n";
echo "SHOP NAME COMPARISON\n";
echo "======================================\n\n";

echo "Shops in scraped JSON (" . count($scrapedShops) . "):\n";
foreach ($scrapedShops as $shop) {
    echo "  - {$shop}\n";
}

echo "\nShops in ShopHelper (" . count($helperShops) . "):\n";
foreach ($helperShops as $shop) {
    echo "  - {$shop}\n";
}

echo "\n======================================\n";
echo "MATCHING ANALYSIS\n";
echo "======================================\n";

$matched = 0;
$unmatched = [];

foreach ($scrapedShops as $scraped) {
    $found = false;
    foreach ($helperShops as $helper) {
        if ($scraped === $helper) {
            $found = true;
            $matched++;
            break;
        }
    }
    if (!$found) {
        $unmatched[] = $scraped;
    }
}

echo "Matched shops: {$matched}\n";
echo "Unmatched shops: " . count($unmatched) . "\n\n";

if (count($unmatched) > 0) {
    echo "Unmatched shop names from scraper:\n";
    foreach ($unmatched as $shop) {
        echo "  - '{$shop}'\n";
    }
}
