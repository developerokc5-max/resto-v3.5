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

    $kpis = [
        'stores_online' => $totalStores,
        'items_off'     => $totalItemsOff,
        'addons_off'    => 0,
        'alerts'        => $totalChanges,
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
