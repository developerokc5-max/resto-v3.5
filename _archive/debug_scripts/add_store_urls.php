#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" ADD STORE PLATFORM URLs ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

// Check if stores table exists and has url columns
$hasGrabUrl = Schema::hasColumn('items', 'grab_url');
$hasFoodpandaUrl = Schema::hasColumn('items', 'foodpanda_url');
$hasDeliverooUrl = Schema::hasColumn('items', 'deliveroo_url');

echo "Current table structure:\n";
echo "  - grab_url column: " . ($hasGrabUrl ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "  - foodpanda_url column: " . ($hasFoodpandaUrl ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "  - deliveroo_url column: " . ($hasDeliverooUrl ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "\n";

if (!$hasGrabUrl || !$hasFoodpandaUrl || !$hasDeliverooUrl) {
    echo "❌ You need to add URL columns to your items/shops table first.\n\n";
    echo "Run this SQL:\n\n";
    echo "ALTER TABLE items ADD COLUMN grab_url TEXT NULL;\n";
    echo "ALTER TABLE items ADD COLUMN foodpanda_url TEXT NULL;\n";
    echo "ALTER TABLE items ADD COLUMN deliveroo_url TEXT NULL;\n\n";
    exit(1);
}

// Get unique shop names
$shops = DB::table('items')
    ->select('shop_name', 'shop_id')
    ->distinct()
    ->orderBy('shop_name')
    ->limit(20)
    ->get();

echo "Found " . $shops->count() . " unique shops:\n";
echo str_repeat("─", 80) . "\n";

foreach ($shops as $idx => $shop) {
    echo sprintf("%2d. [%s] %s\n", $idx + 1, $shop->shop_id, $shop->shop_name);
}

echo "\n";
echo "TO ADD URLs, you need to:\n";
echo "1. Find your store on Grab/FoodPanda/Deliveroo\n";
echo "2. Copy the full URL\n";
echo "3. Update the database\n\n";

echo "Example URLs:\n";
echo "  Grab:       https://food.grab.com/sg/en/restaurant/your-store-name/YOUR-ID\n";
echo "  FoodPanda:  https://www.foodpanda.sg/restaurant/your-store-slug\n";
echo "  Deliveroo:  https://deliveroo.com.sg/menu/singapore/your-store-slug\n\n";

// Sample update command
echo "Example update SQL:\n";
echo "UPDATE items SET \n";
echo "  grab_url = 'https://food.grab.com/sg/en/restaurant/ok-chicken-rice/1-ABC123',\n";
echo "  foodpanda_url = 'https://www.foodpanda.sg/restaurant/s7bw/ok-chicken-rice',\n";
echo "  deliveroo_url = 'https://deliveroo.com.sg/menu/singapore/ok-chicken-rice'\n";
echo "WHERE shop_name = 'OK CHICKEN RICE @ Toa Payoh';\n\n";

echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" After adding URLs, run the scraper! ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";
