<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Item Status History Data ===\n\n";

// Get all records
$allRecords = DB::table('item_status_history')->get();
echo "Total records: " . $allRecords->count() . "\n\n";

// Group by shop_id
$byShop = DB::table('item_status_history')
    ->select('shop_id', 'shop_name', DB::raw('COUNT(*) as count'))
    ->groupBy('shop_id', 'shop_name')
    ->get();

echo "Records by shop:\n";
foreach ($byShop as $shop) {
    echo "  {$shop->shop_id} - {$shop->shop_name}: {$shop->count} records\n";
}

echo "\n=== Sample Records ===\n";
$sample = DB::table('item_status_history')->limit(5)->get();
foreach ($sample as $record) {
    echo "\nShop: {$record->shop_name} ({$record->shop_id})\n";
    echo "Item: {$record->item_name}\n";
    echo "Platform: {$record->platform}\n";
    echo "Status: " . ($record->is_available ? 'Online' : 'Offline') . "\n";
    echo "Changed at: {$record->changed_at}\n";
}
