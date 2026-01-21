<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "  COMPLETE RESTOSUITE SCRAPING READINESS REPORT\n";
echo "  Generated: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

// First, check what tables we have
$tables = DB::select("SHOW TABLES");
echo "Available tables:\n";
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    echo "  - $tableName\n";
}
echo "\n";

// Get all unique shop names from items table
echo "Getting shop information from items table...\n\n";

$shopStats = DB::table('items')
    ->select(
        'shop_id',
        'shop_name',
        'platform',
        DB::raw('COUNT(*) as total_items'),
        DB::raw('SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as online_items'),
        DB::raw('SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_items')
    )
    ->groupBy('shop_id', 'shop_name', 'platform')
    ->orderBy('shop_name')
    ->orderBy('platform')
    ->get();

// Organize by shop
$shops = [];
foreach ($shopStats as $stat) {
    if (!isset($shops[$stat->shop_name])) {
        $shops[$stat->shop_name] = [
            'name' => $stat->shop_name,
            'platforms' => [],
            'total_platforms' => 0,
            'total_items' => 0
        ];
    }
    
    $shops[$stat->shop_name]['platforms'][$stat->platform] = [
        'shop_id' => $stat->shop_id,
        'total_items' => $stat->total_items,
        'online_items' => $stat->online_items,
        'offline_items' => $stat->offline_items
    ];
    $shops[$stat->shop_name]['total_platforms']++;
    $shops[$stat->shop_name]['total_items'] += $stat->total_items;
}

// Categorize shops
$fully_ready = [];
$partial_ready = [];
$single_platform = [];

foreach ($shops as $shopName => $shopData) {
    if ($shopData['total_platforms'] >= 3) {
        $fully_ready[] = $shopData;
    } elseif ($shopData['total_platforms'] == 2) {
        $partial_ready[] = $shopData;
    } else {
        $single_platform[] = $shopData;
    }
}

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

echo "Total unique shops: " . count($shops) . "\n";
echo "  ✓ Fully Ready (3 platforms): " . count($fully_ready) . " stores\n";
echo "  ⚠ Partial (2 platforms): " . count($partial_ready) . " stores\n";
echo "  ⚠ Single platform only: " . count($single_platform) . " stores\n\n";

$totalPlatformBindings = count($fully_ready) * 3 + count($partial_ready) * 2 + count($single_platform);
echo "Total platform bindings: $totalPlatformBindings\n";
echo "Total items tracked: " . array_sum(array_column($shops, 'total_items')) . "\n\n";

// Display fully ready stores
if (count($fully_ready) > 0) {
    echo "═══════════════════════════════════════════════════════════════════════════\n";
    echo "  ✓ FULLY READY STORES (ALL 3 PLATFORMS)\n";
    echo "═══════════════════════════════════════════════════════════════════════════\n\n";
    
    foreach ($fully_ready as $shop) {
        echo "📍 {$shop['name']}\n";
        echo "   Total Items: {$shop['total_items']} across {$shop['total_platforms']} platforms\n";
        
        foreach (['grab', 'deliveroo', 'foodpanda'] as $platform) {
            if (isset($shop['platforms'][$platform])) {
                $p = $shop['platforms'][$platform];
                $onlinePercent = $p['total_items'] > 0 ? round(($p['online_items'] / $p['total_items']) * 100, 1) : 0;
                echo "   └─ " . ucfirst($platform) . ": {$p['total_items']} items ({$p['online_items']} online, {$p['offline_items']} offline - {$onlinePercent}% available)\n";
                echo "      Shop ID: {$p['shop_id']}\n";
            } else {
                echo "   └─ " . ucfirst($platform) . ": NOT BOUND ✗\n";
            }
        }
        echo "\n";
    }
}

// Display partial ready stores
if (count($partial_ready) > 0) {
    echo "═══════════════════════════════════════════════════════════════════════════\n";
    echo "  ⚠ PARTIALLY READY STORES (2 PLATFORMS)\n";
    echo "═══════════════════════════════════════════════════════════════════════════\n\n";
    
    foreach ($partial_ready as $shop) {
        echo "📍 {$shop['name']}\n";
        echo "   Total Items: {$shop['total_items']} across {$shop['total_platforms']} platforms\n";
        
        foreach (['grab', 'deliveroo', 'foodpanda'] as $platform) {
            if (isset($shop['platforms'][$platform])) {
                $p = $shop['platforms'][$platform];
                $onlinePercent = $p['total_items'] > 0 ? round(($p['online_items'] / $p['total_items']) * 100, 1) : 0;
                echo "   └─ " . ucfirst($platform) . ": {$p['total_items']} items ({$p['online_items']} online, {$p['offline_items']} offline - {$onlinePercent}% available) ✓\n";
            } else {
                echo "   └─ " . ucfirst($platform) . ": NOT BOUND ✗\n";
            }
        }
        echo "\n";
    }
}

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "  PRODUCTION READINESS STATUS\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

if (count($fully_ready) > 0) {
    echo "✅ PRODUCTION READY!\n\n";
    echo "You have " . count($fully_ready) . " stores fully configured with all 3 platforms.\n";
    echo "Total platforms ready for scraping: " . (count($fully_ready) * 3) . "\n\n";
    
    echo "To start automated scraping:\n";
    echo "  php artisan scrape:restosuite-production\n\n";
    
    echo "The scraper will monitor these stores:\n";
    foreach ($fully_ready as $shop) {
        echo "  - {$shop['name']}\n";
    }
} else {
    echo "⚠ NOT READY FOR PRODUCTION\n\n";
    echo "No stores have all 3 platforms configured yet.\n";
    echo "Please complete platform binding before running production scraper.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "Report generated successfully.\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
