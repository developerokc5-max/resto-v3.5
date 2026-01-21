#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" PLATFORM ITEMS REPORT - " . date('Y-m-d H:i:s'), 78) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

// ============================================================================
// OVERALL SUMMARY
// ============================================================================
echo "📊 OVERALL SUMMARY\n";
echo str_repeat("─", 80) . "\n";

$totalItems = DB::table('items')->count();
$totalOnline = DB::table('items')->where('is_available', 1)->count();
$totalOffline = DB::table('items')->where('is_available', 0)->count();

echo sprintf("  Total Items:    %5d\n", $totalItems);
echo sprintf("  Online:         %5d (%.1f%%)\n", $totalOnline, $totalItems > 0 ? ($totalOnline/$totalItems*100) : 0);
echo sprintf("  Offline:        %5d (%.1f%%)\n", $totalOffline, $totalItems > 0 ? ($totalOffline/$totalItems*100) : 0);

// ============================================================================
// BY PLATFORM
// ============================================================================
echo "\n📱 BY PLATFORM\n";
echo str_repeat("─", 80) . "\n";

$platforms = DB::select("
    SELECT
        platform,
        COUNT(*) as total_items,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as online_items,
        SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_items,
        ROUND(SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as offline_percentage
    FROM items
    GROUP BY platform
    ORDER BY platform
");

echo sprintf("  %-15s %10s %10s %10s %10s\n",
    "Platform", "Total", "Online", "Offline", "% Offline");
echo sprintf("  %-15s %10s %10s %10s %10s\n",
    str_repeat("─", 15), str_repeat("─", 10), str_repeat("─", 10), str_repeat("─", 10), str_repeat("─", 10));

foreach ($platforms as $p) {
    echo sprintf("  %-15s %10d %10d %10d %9.1f%%\n",
        ucfirst($p->platform),
        $p->total_items,
        $p->online_items,
        $p->offline_items,
        $p->offline_percentage
    );
}

// ============================================================================
// BY SHOP (TOP 10)
// ============================================================================
echo "\n🏪 BY SHOP (Top 10 Shops with Most Items)\n";
echo str_repeat("─", 80) . "\n";

$shops = DB::select("
    SELECT
        shop_name,
        COUNT(*) as total_items,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as online_items,
        SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_items
    FROM items
    GROUP BY shop_name
    ORDER BY total_items DESC
    LIMIT 10
");

echo sprintf("  %-40s %10s %10s %10s\n",
    "Shop Name", "Total", "Online", "Offline");
echo sprintf("  %-40s %10s %10s %10s\n",
    str_repeat("─", 40), str_repeat("─", 10), str_repeat("─", 10), str_repeat("─", 10));

foreach ($shops as $shop) {
    $name = strlen($shop->shop_name) > 40 ? substr($shop->shop_name, 0, 37) . '...' : $shop->shop_name;
    echo sprintf("  %-40s %10d %10d %10d\n",
        $name,
        $shop->total_items,
        $shop->online_items,
        $shop->offline_items
    );
}

// ============================================================================
// OFFLINE ITEMS BY PLATFORM
// ============================================================================
echo "\n❌ OFFLINE ITEMS BY PLATFORM (Sample - First 10 per platform)\n";
echo str_repeat("─", 80) . "\n";

foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
    $offlineItems = DB::table('items')
        ->where('platform', $platform)
        ->where('is_available', 0)
        ->orderBy('shop_name')
        ->orderBy('name')
        ->limit(10)
        ->get();

    if ($offlineItems->count() > 0) {
        $totalOfflineForPlatform = DB::table('items')
            ->where('platform', $platform)
            ->where('is_available', 0)
            ->count();

        echo "\n  " . strtoupper($platform) . " (Showing 10 of {$totalOfflineForPlatform}):\n";
        echo "  " . str_repeat("─", 78) . "\n";

        foreach ($offlineItems as $item) {
            $shopName = strlen($item->shop_name) > 30 ? substr($item->shop_name, 0, 27) . '...' : $item->shop_name;
            $itemName = strlen($item->name) > 40 ? substr($item->name, 0, 37) . '...' : $item->name;
            echo sprintf("    %-30s | %s\n", $shopName, $itemName);
        }
    }
}

// ============================================================================
// RECENT CHANGES (if history table exists)
// ============================================================================
try {
    $historyCount = DB::table('item_status_history')->count();
    $historyExists = $historyCount >= 0;
} catch (\Exception $e) {
    $historyExists = false;
}

if ($historyExists) {
    echo "\n🔄 RECENT STATUS CHANGES (Last 24 hours)\n";
    echo str_repeat("─", 80) . "\n";

    $recentChanges = DB::table('item_status_history')
        ->where('changed_at', '>=', DB::raw("datetime('now', '-24 hours')"))
        ->orderBy('changed_at', 'desc')
        ->limit(10)
        ->get();

    if ($recentChanges->count() > 0) {
        echo sprintf("  %-20s %-15s %-30s %s\n",
            "Time", "Platform", "Shop", "Item");
        echo sprintf("  %-20s %-15s %-30s %s\n",
            str_repeat("─", 20), str_repeat("─", 15), str_repeat("─", 30), str_repeat("─", 20));

        foreach ($recentChanges as $change) {
            $shopName = strlen($change->shop_name) > 30 ? substr($change->shop_name, 0, 27) . '...' : $change->shop_name;
            $itemName = strlen($change->item_name) > 20 ? substr($change->item_name, 0, 17) . '...' : $change->item_name;
            $status = $change->is_available ? '✓ Online' : '✗ Offline';

            echo sprintf("  %-20s %-15s %-30s %s [%s]\n",
                date('Y-m-d H:i', strtotime($change->changed_at)),
                $change->platform,
                $shopName,
                $itemName,
                $status
            );
        }
    } else {
        echo "  No changes in the last 24 hours\n";
    }
}

// ============================================================================
// ITEMS WITH IMAGES
// ============================================================================
echo "\n🖼️  IMAGE COVERAGE\n";
echo str_repeat("─", 80) . "\n";

$imageStats = DB::select("
    SELECT
        platform,
        COUNT(*) as total,
        SUM(CASE WHEN image_url IS NOT NULL AND image_url != '' THEN 1 ELSE 0 END) as with_image,
        ROUND(SUM(CASE WHEN image_url IS NOT NULL AND image_url != '' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as percentage
    FROM items
    GROUP BY platform
    ORDER BY platform
");

echo sprintf("  %-15s %10s %15s %15s\n",
    "Platform", "Total", "With Images", "% Coverage");
echo sprintf("  %-15s %10s %15s %15s\n",
    str_repeat("─", 15), str_repeat("─", 10), str_repeat("─", 15), str_repeat("─", 15));

foreach ($imageStats as $stat) {
    echo sprintf("  %-15s %10d %15d %14.1f%%\n",
        ucfirst($stat->platform),
        $stat->total,
        $stat->with_image,
        $stat->percentage
    );
}

echo "\n";
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" END OF REPORT ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";
