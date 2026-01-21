<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Items Management Query\n";
echo "==============================\n\n";

// Count total items
$totalItems = DB::table('items')->count();
echo "Total items in database: $totalItems\n\n";

// Get first 5 items to test grouping
echo "Testing grouping logic with first 15 items...\n";
$items = DB::table('items')
    ->select('id', 'item_id', 'shop_name', 'name', 'sku', 'category', 'price', 'is_available', 'platform')
    ->limit(15)
    ->orderBy('shop_name')
    ->orderBy('name')
    ->orderBy('platform')
    ->get();

$itemsGrouped = [];
foreach ($items as $item) {
    $key = $item->shop_name . '|' . $item->name;

    if (!isset($itemsGrouped[$key])) {
        $itemsGrouped[$key] = [
            'shop_name' => $item->shop_name,
            'name' => $item->name,
            'sku' => $item->sku,
            'category' => $item->category,
            'price' => $item->price,
            'platforms' => [
                'grab' => null,
                'foodpanda' => null,
                'deliveroo' => null,
            ],
            'any_available' => false,
        ];
    }

    $itemsGrouped[$key]['platforms'][$item->platform] = [
        'id' => $item->id,
        'is_available' => (bool)$item->is_available,
    ];

    if ($item->is_available) {
        $itemsGrouped[$key]['any_available'] = true;
    }
}

echo "Grouped into " . count($itemsGrouped) . " unique items\n\n";

echo "Sample grouped items:\n";
$count = 0;
foreach ($itemsGrouped as $item) {
    if ($count++ >= 3) break;
    echo "  - {$item['name']} ({$item['shop_name']})\n";
    echo "    Grab: " . ($item['platforms']['grab'] ? ($item['platforms']['grab']['is_available'] ? 'ON' : 'OFF') : '-') . "\n";
    echo "    FoodPanda: " . ($item['platforms']['foodpanda'] ? ($item['platforms']['foodpanda']['is_available'] ? 'ON' : 'OFF') : '-') . "\n";
    echo "    Deliveroo: " . ($item['platforms']['deliveroo'] ? ($item['platforms']['deliveroo']['is_available'] ? 'ON' : 'OFF') : '-') . "\n\n";
}

echo "Query test successful!\n";
