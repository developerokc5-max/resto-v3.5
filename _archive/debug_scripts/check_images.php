<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Image Data Check ===\n\n";

$withImages = DB::table('items')->whereNotNull('image_url')->count();
$total = DB::table('items')->count();

echo "Items with images: $withImages / $total\n\n";

echo "Sample items with images:\n";
$sample = DB::table('items')->whereNotNull('image_url')->limit(5)->get(['name', 'image_url', 'platform']);
foreach($sample as $item) {
    echo "- {$item->name} ({$item->platform})\n";
    echo "  Image: " . substr($item->image_url, 0, 100) . "...\n";
}

echo "\n=== Checking RestoSuite Snapshots ===\n";
$snapshotsWithImages = DB::table('restosuite_item_snapshots')->whereNotNull('image_url')->count();
$totalSnapshots = DB::table('restosuite_item_snapshots')->count();
echo "Snapshots with images: $snapshotsWithImages / $totalSnapshots\n";

if ($snapshotsWithImages > 0) {
    echo "\nSample snapshot images:\n";
    $sampleSnap = DB::table('restosuite_item_snapshots')->whereNotNull('image_url')->limit(3)->get(['name', 'image_url']);
    foreach($sampleSnap as $snap) {
        echo "- {$snap->name}\n";
        echo "  Image: {$snap->image_url}\n";
    }
}

echo "\n=== REALITY CHECK ===\n";
echo "❌ Images: Currently NO IMAGES (image_url is NULL in RestoSuite API response)\n";
echo "⚠️  Platform Data: SIMULATED (Grab/FoodPanda/Deliveroo availability is test data)\n";
echo "✅ Item Names/Prices: REAL (from RestoSuite API)\n";
echo "✅ Restaurant Names: REAL (from RestoSuite API)\n";
echo "\nTo get real images and platform data, you need:\n";
echo "1. RestoSuite API to return 'itemImageUrl' field\n";
echo "2. Real platform URLs for Grab/FoodPanda/Deliveroo stores\n";
echo "3. Browser automation (Puppeteer/Selenium) to scrape platform sites\n";
