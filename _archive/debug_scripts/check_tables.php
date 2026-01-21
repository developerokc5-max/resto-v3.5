<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Table Counts ===\n\n";
echo "Items table: " . DB::table('items')->count() . "\n";
echo "Snapshots: " . DB::table('restosuite_item_snapshots')->count() . "\n";
echo "History: " . DB::table('item_status_history')->count() . "\n";
echo "\n=== Sample items ===\n";

$items = DB::table('items')->limit(5)->get();
foreach ($items as $item) {
    echo "- {$item->name} (Shop: {$item->shop_id})\n";
}
