<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Helpers\ShopHelper;

Route::get('/', function () {
    return redirect('/dashboard');
});


Route::get('/dashboard', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false || stripos($info['name'], 'office testing') !== false) {
            $testingShopIds[] = $shopId;
        }
    }

    // Get real data from database (excluding testing outlets)
    $totalStores = DB::table('restosuite_item_snapshots')
        ->whereNotIn('shop_id', $testingShopIds)
        ->distinct('shop_id')
        ->count('shop_id');

    $totalItemsOff = DB::table('restosuite_item_snapshots')
        ->whereNotIn('shop_id', $testingShopIds)
        ->where('is_active', 0)
        ->count();

    $totalChanges = DB::table('restosuite_item_changes')
        ->whereNotIn('shop_id', $testingShopIds)
        ->whereDate('created_at', today())
        ->count();

    // HYBRID: Platform stats
    $platformsOnline = DB::table('platform_status')
        ->whereNotIn('shop_id', $testingShopIds)
        ->where('is_online', 1)
        ->count();

    $platformsTotal = DB::table('platform_status')
        ->whereNotIn('shop_id', $testingShopIds)
        ->count();

    $kpis = [
        'stores_online' => $totalStores,
        'items_off'     => $totalItemsOff,
        'addons_off'    => 0,
        'alerts'        => $totalChanges,
        // HYBRID: Platform KPIs
        'platforms_online' => $platformsOnline,
        'platforms_total' => $platformsTotal,
        'platforms_offline' => $platformsTotal - $platformsOnline,
    ];

    // Get store stats (excluding testing outlets)
    $storeStats = DB::table('restosuite_item_snapshots as s')
        ->select(
            's.shop_id',
            DB::raw('COUNT(*) as total_items'),
            DB::raw('SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) as items_off'),
            DB::raw('MAX(s.updated_at) as last_sync')
        )
        ->whereNotIn('s.shop_id', $testingShopIds)
        ->groupBy('s.shop_id')
        ->get();

    $stores = [];
    foreach ($storeStats as $stat) {
        $shopInfo = $shopMap[$stat->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];

        $recentChanges = DB::table('restosuite_item_changes')
            ->where('shop_id', $stat->shop_id)
            ->whereDate('created_at', today())
            ->count();

        // Get platform status for this shop
        $platformStatus = DB::table('platform_status')
            ->where('shop_id', $stat->shop_id)
            ->get()
            ->keyBy('platform');

        $stores[] = [
            'brand' => $shopInfo['brand'],
            'store' => $shopInfo['name'],
            'shop_id' => $stat->shop_id,
            'status' => 'OPERATING',
            'items_off' => (int) $stat->items_off,
            'addons_off' => 0,
            'alerts' => $recentChanges,
            'total_items' => (int) $stat->total_items,
            'last_change' => $stat->last_sync ? \Carbon\Carbon::parse($stat->last_sync)->diffForHumans() : '—',
            // HYBRID: Platform status from scraping
            'platforms' => [
                'grab' => [
                    'online' => $platformStatus->get('grab')?->is_online ?? null,
                    'items_synced' => $platformStatus->get('grab')?->items_synced ?? 0,
                    'last_checked' => $platformStatus->get('grab')?->last_checked_at ?? null,
                ],
                'foodpanda' => [
                    'online' => $platformStatus->get('foodpanda')?->is_online ?? null,
                    'items_synced' => $platformStatus->get('foodpanda')?->items_synced ?? 0,
                    'last_checked' => $platformStatus->get('foodpanda')?->last_checked_at ?? null,
                ],
                'deliveroo' => [
                    'online' => $platformStatus->get('deliveroo')?->is_online ?? null,
                    'items_synced' => $platformStatus->get('deliveroo')?->items_synced ?? 0,
                    'last_checked' => $platformStatus->get('deliveroo')?->last_checked_at ?? null,
                ],
            ],
        ];
    }

    $lastSyncTime = DB::table('restosuite_item_snapshots')
        ->max('updated_at');

    return view('dashboard', [
        'kpis' => $kpis,
        'stores' => $stores,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('h:i A') : '—',
    ]);
});

// Stores Page
Route::get('/stores', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false || stripos($info['name'], 'office testing') !== false) {
            $testingShopIds[] = $shopId;
        }
    }

    $storeStats = DB::table('restosuite_item_snapshots as s')
        ->select(
            's.shop_id',
            DB::raw('COUNT(*) as total_items'),
            DB::raw('SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) as items_off'),
            DB::raw('MAX(s.updated_at) as last_sync')
        )
        ->whereNotIn('s.shop_id', $testingShopIds)
        ->groupBy('s.shop_id')
        ->get();

    $stores = [];
    foreach ($storeStats as $stat) {
        $shopInfo = $shopMap[$stat->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];

        $recentChanges = DB::table('restosuite_item_changes')
            ->where('shop_id', $stat->shop_id)
            ->whereDate('created_at', today())
            ->count();

        $stores[] = [
            'brand' => $shopInfo['brand'],
            'store' => $shopInfo['name'],
            'shop_id' => $stat->shop_id,
            'status' => 'OPERATING',
            'total_items' => (int) $stat->total_items,
            'items_off' => (int) $stat->items_off,
            'alerts' => $recentChanges,
            'last_change' => $stat->last_sync ? \Carbon\Carbon::parse($stat->last_sync)->diffForHumans() : '—',
        ];
    }

    $lastSyncTime = DB::table('restosuite_item_snapshots')->max('updated_at');

    return view('stores', [
        'stores' => $stores,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('h:i A') : '—',
    ]);
});

// Items Page
Route::get('/items', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false || stripos($info['name'], 'office testing') !== false) {
            $testingShopIds[] = $shopId;
        }
    }

    $items = DB::table('restosuite_item_snapshots')
        ->select('shop_id', 'item_id', 'name', 'price', 'is_active', 'updated_at')
        ->whereNotIn('shop_id', $testingShopIds)
        ->orderBy('updated_at', 'desc')
        ->limit(100)
        ->get();

    $itemsArray = [];
    foreach ($items as $item) {
        $shopInfo = $shopMap[$item->shop_id] ?? ['name' => 'Unknown Store', 'brand' => ''];

        $itemsArray[] = [
            'shop_id' => $item->shop_id,
            'item_id' => $item->item_id,
            'name' => $item->name,
            'price' => $item->price,
            'is_active' => (bool) $item->is_active,
            'shop_name' => $shopInfo['name'],
            'image_url' => null,
            'last_update' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->diffForHumans() : '—',
        ];
    }

    $lastSyncTime = DB::table('restosuite_item_snapshots')->max('updated_at');

    return view('items', [
        'items' => $itemsArray,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('h:i A') : '—',
    ]);
});

// Store Detail Page
Route::get('/store/{shopId}', function ($shopId) {
    $shopMap = ShopHelper::getShopMap();
    $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown Store', 'brand' => 'Unknown Brand'];

    $items = DB::table('restosuite_item_snapshots')
        ->where('shop_id', $shopId)
        ->orderBy('name')
        ->get();

    $itemsArray = [];
    foreach ($items as $item) {
        $itemsArray[] = [
            'name' => $item->name,
            'price' => $item->price,
            'is_active' => (bool) $item->is_active,
            'last_update' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->diffForHumans() : '—',
        ];
    }

    $totalItems = count($itemsArray);
    $activeItems = count(array_filter($itemsArray, fn($i) => $i['is_active']));
    $itemsOff = $totalItems - $activeItems;
    $changesToday = DB::table('restosuite_item_changes')
        ->where('shop_id', $shopId)
        ->whereDate('created_at', today())
        ->count();

    $store = [
        'shop_id' => $shopId,
        'name' => $shopInfo['name'],
        'brand' => $shopInfo['brand'],
        'status' => 'OPERATING',
        'total_items' => $totalItems,
        'active_items' => $activeItems,
        'items_off' => $itemsOff,
        'changes_today' => $changesToday,
    ];

    $lastSyncTime = DB::table('restosuite_item_snapshots')->max('updated_at');

    return view('store-detail', [
        'store' => $store,
        'items' => $itemsArray,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('h:i A') : '—',
    ]);
});

// Item Tracking History Page
Route::get('/item-tracking', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false || stripos($info['name'], 'office testing') !== false) {
            $testingShopIds[] = $shopId;
        }
    }

    $changes = DB::table('restosuite_item_changes as c')
        ->join('restosuite_item_snapshots as s', function ($join) {
            $join->on('c.shop_id', '=', 's.shop_id')
                 ->on('c.item_id', '=', 's.item_id');
        })
        ->select('c.*', 's.name as item_name')
        ->whereNotIn('c.shop_id', $testingShopIds)
        ->whereDate('c.created_at', today())
        ->orderBy('c.created_at', 'desc')
        ->limit(50)
        ->get();

    $changesArray = [];
    $turnedOff = 0;
    $turnedOn = 0;

    foreach ($changes as $change) {
        $shopInfo = $shopMap[$change->shop_id] ?? ['name' => 'Unknown Store', 'brand' => ''];
        $changeData = json_decode($change->change_json, true);

        if (isset($changeData['is_active'])) {
            if ($changeData['is_active']['to'] == 0) {
                $turnedOff++;
            } else {
                $turnedOn++;
            }
        }

        $changesArray[] = [
            'shop_id' => $change->shop_id,
            'shop_name' => $shopInfo['name'],
            'item_name' => $change->item_name,
            'changes' => $changeData,
            'timestamp' => $change->created_at ? \Carbon\Carbon::parse($change->created_at)->diffForHumans() : '—',
        ];
    }

    $lastSyncTime = DB::table('restosuite_item_snapshots')->max('updated_at');

    return view('item-tracking', [
        'changes' => $changesArray,
        'stats' => [
            'turned_off' => $turnedOff,
            'turned_on' => $turnedOn,
        ],
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('h:i A') : '—',
    ]);
});

// HYBRID: Platform Status Page
Route::get('/platforms', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false || stripos($info['name'], 'office testing') !== false) {
            $testingShopIds[] = $shopId;
        }
    }

    // Get all platform statuses
    $platformStatuses = DB::table('platform_status')
        ->whereNotIn('shop_id', $testingShopIds)
        ->orderBy('shop_id')
        ->orderBy('platform')
        ->get();

    // Group by shop
    $shopsPlatforms = [];
    foreach ($platformStatuses as $status) {
        if (!isset($shopsPlatforms[$status->shop_id])) {
            $shopInfo = $shopMap[$status->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];
            $shopsPlatforms[$status->shop_id] = [
                'shop_id' => $status->shop_id,
                'shop_name' => $shopInfo['name'],
                'brand' => $shopInfo['brand'],
                'platforms' => [],
            ];
        }

        $shopsPlatforms[$status->shop_id]['platforms'][$status->platform] = [
            'is_online' => (bool) $status->is_online,
            'items_synced' => $status->items_synced ?? 0,
            'items_total' => $status->items_total ?? 0,
            'last_checked' => $status->last_checked_at ? \Carbon\Carbon::parse($status->last_checked_at)->diffForHumans() : 'Never',
            'status' => $status->last_check_status ?? 'unknown',
        ];
    }

    // Calculate statistics
    $totalPlatforms = $platformStatuses->count();
    $onlinePlatforms = $platformStatuses->where('is_online', 1)->count();
    $offlinePlatforms = $totalPlatforms - $onlinePlatforms;

    $platformStats = [];
    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
        $platformData = $platformStatuses->where('platform', $platform);
        $total = $platformData->count();
        $online = $platformData->where('is_online', 1)->count();

        $platformStats[$platform] = [
            'total' => $total,
            'online' => $online,
            'offline' => $total - $online,
            'percentage' => $total > 0 ? round(($online / $total) * 100, 2) : 0,
        ];
    }

    $lastScrapeTime = DB::table('platform_status')->max('last_checked_at');

    return view('platforms', [
        'shops' => array_values($shopsPlatforms),
        'stats' => [
            'total' => $totalPlatforms,
            'online' => $onlinePlatforms,
            'offline' => $offlinePlatforms,
            'percentage' => $totalPlatforms > 0 ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
        ],
        'platformStats' => $platformStats,
        'lastScrape' => $lastScrapeTime ? \Carbon\Carbon::parse($lastScrapeTime)->format('h:i A') : '—',
    ]);
});
