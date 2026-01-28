<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\ShopHelper;

Route::get('/', function () {
    return redirect('/dashboard');
});


Route::get('/dashboard', function () {
    $shopMap = ShopHelper::getShopMap();

    // NO FILTERING - Show ALL stores including testing outlets
    $testingShopIds = []; // Empty array - no exclusions

    // HYBRID: Get real data from database (ALL stores)
    // Try RestoSuite API data first
    $totalStores = DB::table('restosuite_item_snapshots')
        ->distinct('shop_id')
        ->count('shop_id');

    // Fallback to platform_status if no API data
    if ($totalStores == 0) {
        $totalStores = DB::table('platform_status')
            ->distinct('shop_id')
            ->count('shop_id');
    }

    // HYBRID: Items OFF - count from items table
    $totalItemsOff = DB::table('items')
        ->where('is_available', 0)
        ->count();

    // If no data in items table, fallback to offline platforms count
    if ($totalItemsOff == 0) {
        $totalItemsOff = DB::table('platform_status')
            ->where('is_online', 0)
            ->count();
    }

    // HYBRID: Active Alerts - count stores with at least one offline platform
    $totalChanges = DB::table('restosuite_item_changes')
        ->whereDate('created_at', today())
        ->count();

    // If no API data, count stores with offline platforms
    if ($totalChanges == 0) {
        $totalChanges = DB::table('platform_status')
            ->where('is_online', 0)
            ->distinct('shop_id')
            ->count('shop_id');
    }

    // HYBRID: Platform stats
    $platformsOnline = DB::table('platform_status')
        ->where('is_online', 1)
        ->count();

    $platformsTotal = DB::table('platform_status')
        ->count();

    $kpis = [
        'stores_online' => $totalStores,
        'items_off'     => (int) $totalItemsOff,
        'addons_off'    => 0,
        'alerts'        => (int) $totalChanges,
        // HYBRID: Platform KPIs
        'platforms_online' => $platformsOnline,
        'platforms_total' => $platformsTotal,
        'platforms_offline' => $platformsTotal - $platformsOnline,
    ];

    // HYBRID: Get stores from either RestoSuite API or Platform Status
    // Try RestoSuite data first
    $storeStats = DB::table('restosuite_item_snapshots as s')
        ->select(
            's.shop_id',
            DB::raw('COUNT(*) as total_items'),
            DB::raw('SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) as items_off'),
            DB::raw('MAX(s.updated_at) as last_sync')
        )
        ->groupBy('s.shop_id')
        ->get();

    $stores = [];

    if ($storeStats->count() > 0) {
        // We have RestoSuite API data - use it as primary source

        // Get offline items count per shop per platform from items table
        $offlineItemsCounts = DB::table('items')
            ->select('shop_name', 'platform', DB::raw('COUNT(*) as offline_count'))
            ->where('is_available', 0)
            ->groupBy('shop_name', 'platform')
            ->get()
            ->keyBy(function ($item) {
                return $item->shop_name . '|' . $item->platform;
            });

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

            // Get offline items count for each platform
            $grabOffline = $offlineItemsCounts->get($shopInfo['name'] . '|grab')?->offline_count ?? 0;
            $foodpandaOffline = $offlineItemsCounts->get($shopInfo['name'] . '|foodpanda')?->offline_count ?? 0;
            $deliverooOffline = $offlineItemsCounts->get($shopInfo['name'] . '|deliveroo')?->offline_count ?? 0;

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
                        'offline_items' => (int) $grabOffline,
                    ],
                    'foodpanda' => [
                        'online' => $platformStatus->get('foodpanda')?->is_online ?? null,
                        'items_synced' => $platformStatus->get('foodpanda')?->items_synced ?? 0,
                        'last_checked' => $platformStatus->get('foodpanda')?->last_checked_at ?? null,
                        'offline_items' => (int) $foodpandaOffline,
                    ],
                    'deliveroo' => [
                        'online' => $platformStatus->get('deliveroo')?->is_online ?? null,
                        'items_synced' => $platformStatus->get('deliveroo')?->items_synced ?? 0,
                        'last_checked' => $platformStatus->get('deliveroo')?->last_checked_at ?? null,
                        'offline_items' => (int) $deliverooOffline,
                    ],
                ],
            ];
        }
    } else {
        // Fallback: Use platform_status table directly
        $platformStatuses = DB::table('platform_status')
            ->orderBy('shop_id')
            ->get();

        // Get offline items count per shop per platform from items table
        $offlineItemsCounts = DB::table('items')
            ->select('shop_name', 'platform', DB::raw('COUNT(*) as offline_count'))
            ->where('is_available', 0)
            ->groupBy('shop_name', 'platform')
            ->get()
            ->keyBy(function ($item) {
                return $item->shop_name . '|' . $item->platform;
            });

        // Group by shop_id
        $shopsPlatforms = [];
        foreach ($platformStatuses as $status) {
            if (!isset($shopsPlatforms[$status->shop_id])) {
                $shopInfo = $shopMap[$status->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];
                $shopsPlatforms[$status->shop_id] = [
                    'shop_id' => $status->shop_id,
                    'brand' => $shopInfo['brand'],
                    'store' => $shopInfo['name'],
                    'store_name' => $status->store_name,
                    'platforms' => [],
                ];
            }

            // Get offline items count for this shop + platform
            $offlineKey = $status->store_name . '|' . $status->platform;
            $offlineCount = $offlineItemsCounts->get($offlineKey)?->offline_count ?? 0;

            $shopsPlatforms[$status->shop_id]['platforms'][$status->platform] = [
                'online' => (bool) $status->is_online,
                'items_synced' => $status->items_synced ?? 0,
                'last_checked' => $status->last_checked_at,
                'offline_items' => (int) $offlineCount,
            ];
        }

        // Convert to stores array format
        foreach ($shopsPlatforms as $shopId => $shopData) {
            $offlineCount = 0;
            foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                if (isset($shopData['platforms'][$platform]) && !$shopData['platforms'][$platform]['online']) {
                    $offlineCount++;
                }
            }

            $onlineCount = 3 - $offlineCount;
            if ($onlineCount === 3) {
                $overallStatus = 'all_online';
            } elseif ($onlineCount === 0) {
                $overallStatus = 'all_offline';
            } else {
                $overallStatus = 'mixed';
            }

            $stores[] = [
                'brand' => $shopData['brand'],
                'store' => $shopData['store'],
                'shop_id' => $shopId,
                'status' => 'OPERATING',
                'items_off' => 0,
                'addons_off' => 0,
                'alerts' => 0,
                'total_items' => 0,
                'last_change' => '—',
                'platform_offline_count' => $offlineCount,
                'platform_online_count' => $onlineCount,
                'overall_status' => $overallStatus,
                'platforms' => $shopData['platforms'],
            ];
        }
    }


    // HYBRID: Check both RestoSuite and Platform Status for last sync
    $lastSyncTime = DB::table('restosuite_item_snapshots')
        ->max('updated_at');

    if (!$lastSyncTime) {
        // Fallback to platform_status last_checked_at
        $lastSyncTime = DB::table('platform_status')
            ->max('last_checked_at');
    }

    return view('dashboard', [
        'kpis' => $kpis,
        'stores' => $stores,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
    ]);
});

// Stores Page
Route::get('/stores', function () {
    $shopMap = ShopHelper::getShopMap();

    // Get all shops from shops table (populated by items scraper)
    $allShops = DB::table('shops')
        ->orderBy('shop_name')
        ->get();

    $stores = [];
    foreach ($allShops as $shop) {
        // Get items count for this shop from items table
        // Since items table has 3 rows per unique item (one per platform: grab, foodpanda, deliveroo)
        // we need to count distinct items by name+category, then get the total across all platforms
        $totalUniqueItems = DB::table('items')
            ->where('shop_name', $shop->shop_name)
            ->select(DB::raw('COUNT(DISTINCT (name || "|" || category)) as count'))
            ->value('count');

        // For items_off, count how many unique items have at least one platform unavailable
        $itemsOffCount = DB::table('items')
            ->where('shop_name', $shop->shop_name)
            ->where('is_available', 0)
            ->select(DB::raw('COUNT(DISTINCT (name || "|" || category)) as count'))
            ->value('count');

        // Get platform status for this shop (match by store_name since shops table uses shop_name)
        $platformStatus = DB::table('platform_status')
            ->where('store_name', $shop->shop_name)
            ->get()
            ->keyBy('platform');

        // Count online/offline platforms
        $onlineCount = 0;
        $offlineCount = 0;
        foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
            if ($platformStatus->has($platform)) {
                if ($platformStatus->get($platform)->is_online) {
                    $onlineCount++;
                } else {
                    $offlineCount++;
                }
            }
        }

        // Determine overall status
        if ($onlineCount === 3) {
            $status = 'all_online';
            $statusText = 'All Platforms Online';
        } elseif ($onlineCount === 0) {
            $status = 'all_offline';
            $statusText = 'All Platforms Offline';
        } else {
            $status = 'partial_offline';
            $statusText = "{$offlineCount}/3 Offline";
        }

        $shopInfo = $shopMap[$shop->shop_id] ?? ['name' => $shop->shop_name, 'brand' => $shop->organization_name ?? 'Unknown'];

        $stores[] = [
            'brand' => $shopInfo['brand'],
            'store' => $shopInfo['name'],
            'shop_id' => $shop->shop_id,
            'status' => $status,
            'status_text' => $statusText,
            'platforms_online' => $onlineCount,
            'platforms_offline' => $offlineCount,
            'total_items' => (int) ($totalUniqueItems ?? 0),
            'items_off' => (int) ($itemsOffCount ?? 0),
            'alerts' => 0,
            'last_change' => $shop->last_synced_at ? \Carbon\Carbon::parse($shop->last_synced_at)->diffForHumans() : '—',
        ];
    }

    // Get last sync time from shops or platform_status table
    $lastSyncTime = DB::table('shops')->max('last_synced_at');
    if (!$lastSyncTime) {
        $lastSyncTime = DB::table('platform_status')->max('last_checked_at');
    }

    return view('stores', [
        'stores' => $stores,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
    ]);
});

// Store Detail Page - Show all items for a specific store
Route::get('/store/{shop_id}', function ($shop_id) {
    $shopMap = ShopHelper::getShopMap();

    // Get shop info
    $shop = DB::table('shops')->where('shop_id', $shop_id)->first();

    if (!$shop) {
        abort(404, 'Store not found');
    }

    $shopInfo = $shopMap[$shop_id] ?? ['name' => $shop->shop_name, 'brand' => 'Unknown'];

    // Get all items for this shop grouped by name+category (across all platforms)
    $items = DB::table('items')
        ->where('shop_name', $shop->shop_name)
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    // Group items by unique item (name + category)
    $groupedItems = [];
    foreach ($items as $item) {
        $key = $item->name . '|' . $item->category;

        if (!isset($groupedItems[$key])) {
            $groupedItems[$key] = [
                'name' => $item->name,
                'category' => $item->category,
                'image_url' => $item->image_url,
                'price' => $item->price,
                'platforms' => [],
                'all_active' => true,
            ];
        }

        $groupedItems[$key]['platforms'][$item->platform] = [
            'is_available' => (bool) $item->is_available,
            'price' => $item->price,
        ];

        // If any platform is unavailable, mark as not all active
        if (!$item->is_available) {
            $groupedItems[$key]['all_active'] = false;
        }
    }

    // Get platform status
    $platformStatus = DB::table('platform_status')
        ->where('store_name', $shop->shop_name)
        ->get()
        ->keyBy('platform');

    return view('store-detail', [
        'shop' => $shop,
        'shopInfo' => $shopInfo,
        'items' => array_values($groupedItems),
        'platformStatus' => $platformStatus,
    ]);
});

// Items Page - Real-time with grouped platforms and pagination
Route::get('/items', function (Request $request) {
    // Get filter parameters
    $selectedRestaurant = $request->get('restaurant');

    // Build query for items
    $query = DB::table('items');

    // Apply restaurant filter if provided
    if ($selectedRestaurant) {
        $query->where('shop_name', $selectedRestaurant);
    }

    // Get all items from the items table
    $allItems = $query
        ->orderBy('shop_name')
        ->orderBy('name')
        ->get();

    // Group items by shop + name to show all 3 platforms together
    $itemsGrouped = [];
    foreach ($allItems as $item) {
        $key = $item->shop_name . '|' . $item->name;

        if (!isset($itemsGrouped[$key])) {
            $itemsGrouped[$key] = [
                'shop_name' => $item->shop_name,
                'name' => $item->name,
                'category' => $item->category,
                'price' => $item->price,
                'image_url' => $item->image_url,
                'sku' => $item->sku,
                'platforms' => [
                    'grab' => false,
                    'foodpanda' => false,
                    'deliveroo' => false,
                ],
            ];
        }

        // Set platform availability
        $itemsGrouped[$key]['platforms'][$item->platform] = (bool)$item->is_available;
    }

    $itemsGrouped = array_values($itemsGrouped);

    // Get ALL restaurants from shops table (including those without items)
    $restaurants = DB::table('shops')
        ->orderBy('shop_name')
        ->pluck('shop_name')
        ->values();

    // Get unique categories
    $categories = DB::table('items')
        ->distinct('category')
        ->whereNotNull('category')
        ->pluck('category')
        ->sort()
        ->values();

    // Calculate stats
    $stats = [
        'total' => count($itemsGrouped),  // Unique items, not total records
        'restaurants' => $restaurants->count(),
        'available' => count(array_filter($itemsGrouped, function($item) {
            return $item['platforms']['grab'] || $item['platforms']['foodpanda'] || $item['platforms']['deliveroo'];
        })),
    ];

    // Pagination - 50 items per page
    $perPage = 50;
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $itemsPaginated = array_slice($itemsGrouped, $offset, $perPage);
    $totalPages = ceil(count($itemsGrouped) / $perPage);

    // Get last update time from items table
    $lastUpdateTime = DB::table('items')->max('updated_at');

    return view('items-table', [
        'items' => $itemsPaginated,
        'restaurants' => $restaurants,
        'categories' => $categories,
        'stats' => $stats,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'perPage' => $perPage,
        'totalItems' => count($itemsGrouped),
        'lastUpdate' => $lastUpdateTime ? \Carbon\Carbon::parse($lastUpdateTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
    ]);
});

// Items Management Page - PRODUCTION
Route::get('/items/management', function (Request $request) {
    set_time_limit(60); // Increase timeout for large dataset

    // Get filter parameters
    $shopFilter = $request->get('shop');
    $categoryFilter = $request->get('category');
    $limit = $request->get('limit', 100); // Default show 100 items

    // Build query
    $query = DB::table('items')
        ->select('id', 'item_id', 'shop_name', 'name', 'sku', 'category', 'price', 'is_available', 'platform');

    // Apply filters if provided
    if ($shopFilter) {
        $query->where('shop_name', $shopFilter);
    }
    if ($categoryFilter) {
        $query->where('category', $categoryFilter);
    }

    $items = $query
        ->orderBy('shop_name')
        ->orderBy('name')
        ->orderBy('platform')
        ->limit($limit * 3) // Get enough for limit unique items (3 platforms each)
        ->get();

    // Group items by name and shop (same item across different platforms)
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

    // Limit to requested number of unique items
    $itemsGrouped = array_slice(array_values($itemsGrouped), 0, $limit);

    // Get unique shops and categories for filters
    $shops = DB::table('items')
        ->distinct('shop_name')
        ->pluck('shop_name')
        ->sort()
        ->values();

    $categories = DB::table('items')
        ->distinct('category')
        ->whereNotNull('category')
        ->pluck('category')
        ->sort()
        ->values();

    // Get total unique items count (divide by 3 for 3 platforms)
    $totalUniqueItems = DB::table('items')
        ->select(DB::raw('COUNT(DISTINCT CONCAT(shop_name, "|", name)) as count'))
        ->first()
        ->count ?? 0;

    return view('items-management', [
        'itemsGrouped' => $itemsGrouped,
        'shops' => $shops,
        'categories' => $categories,
        'totalItems' => $totalUniqueItems,
        'limit' => $limit,
    ]);
});

// Store Detail Page
Route::get('/store/{shopId}', function ($shopId) {
    $shopMap = ShopHelper::getShopMap();
    $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown Store', 'brand' => 'Unknown Brand'];

    // Get platform status
    $platformStatus = DB::table('platform_status')
        ->where('shop_id', $shopId)
        ->get()
        ->keyBy('platform');

    $platforms = [
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
    ];

    // Count offline platforms
    $offlineCount = 0;
    foreach ($platforms as $platform) {
        if ($platform['online'] === false) {
            $offlineCount++;
        }
    }

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
        'platforms' => $platforms,
        'offline_count' => $offlineCount,
    ];

    $lastSyncTime = DB::table('platform_status')
        ->where('shop_id', $shopId)
        ->max('last_checked_at');

    if (!$lastSyncTime) {
        $lastSyncTime = DB::table('restosuite_item_snapshots')
            ->where('shop_id', $shopId)
            ->max('updated_at');
    }

    return view('store-detail', [
        'store' => $store,
        'items' => $itemsArray,
        'lastSync' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
        'lastSyncAgo' => $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->diffForHumans() : 'Never',
    ]);
});

// HYBRID: Platform Status Page
Route::get('/platforms', function () {
    $shopMap = ShopHelper::getShopMap();

    // Filter out testing outlets, edge, and depot stores
    $testingShopIds = [];
    foreach ($shopMap as $shopId => $info) {
        if (stripos($info['name'], 'testing') !== false ||
            stripos($info['name'], 'office testing') !== false ||
            stripos($info['name'], 'edge') !== false ||
            stripos($info['name'], 'depot') !== false) {
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
        'lastScrape' => $lastScrapeTime ? \Carbon\Carbon::parse($lastScrapeTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
    ]);
});

// Store Items Page - Shows offline items for a specific store
Route::get('/store/{shopId}/items', function ($shopId) {
    $shopMap = ShopHelper::getShopMap();
    $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown Store', 'brand' => 'Unknown Brand'];

    // Get platform status for this store
    $platformStatus = DB::table('platform_status')
        ->where('shop_id', $shopId)
        ->get()
        ->keyBy('platform');

    // Get ALL items for this store (grouped by platform)
    $allItems = DB::table('items')
        ->where('shop_name', $shopInfo['name'])
        ->orderBy('platform')
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    // Group items by platform and filter offline items
    $offlineItemsByPlatform = [
        'grab' => [],
        'foodpanda' => [],
        'deliveroo' => [],
    ];

    $totalOfflineItems = 0;
    foreach ($allItems as $item) {
        if ($item->is_available == 0) {
            $offlineItemsByPlatform[$item->platform][] = [
                'name' => $item->name,
                'category' => $item->category ?? 'Uncategorized',
                'price' => $item->price,
                'image_url' => $item->image_url,
                'updated_at' => $item->updated_at,
                'sku' => $item->sku,
            ];
            $totalOfflineItems++;
        }
    }

    // Platform configurations
    $platformConfigs = [
        'grab' => [
            'name' => 'Grab',
            'is_online' => $platformStatus->get('grab')?->is_online ?? null,
            'last_checked' => $platformStatus->get('grab')?->last_checked_at ?? null,
            'color' => 'green',
        ],
        'foodpanda' => [
            'name' => 'foodpanda',
            'is_online' => $platformStatus->get('foodpanda')?->is_online ?? null,
            'last_checked' => $platformStatus->get('foodpanda')?->last_checked_at ?? null,
            'color' => 'pink',
        ],
        'deliveroo' => [
            'name' => 'Deliveroo',
            'is_online' => $platformStatus->get('deliveroo')?->is_online ?? null,
            'last_checked' => $platformStatus->get('deliveroo')?->last_checked_at ?? null,
            'color' => 'cyan',
        ],
    ];

    return view('store-items-offline', [
        'shopId' => $shopId,
        'shopName' => $shopInfo['name'],
        'brandName' => $shopInfo['brand'],
        'offlineItemsByPlatform' => $offlineItemsByPlatform,
        'platformConfigs' => $platformConfigs,
        'totalOfflineItems' => $totalOfflineItems,
    ]);
});

// Offline Items Detail Page (shows which items are offline per platform)
Route::get('/offline-items', function () {
    $shopMap = ShopHelper::getShopMap();

    // Get ALL stores (including testing outlets) - no filtering
    $allShopIds = array_keys($shopMap);

    // Get all platform statuses for ALL shops
    $platformStatuses = DB::table('platform_status')
        ->orderBy('shop_id')
        ->orderBy('platform')
        ->get();

    // Group by shop
    $allStores = [];
    foreach ($platformStatuses as $status) {
        if (!isset($allStores[$status->shop_id])) {
            $shopInfo = $shopMap[$status->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];
            $allStores[$status->shop_id] = [
                'shop_id' => $status->shop_id,
                'shop_name' => $shopInfo['name'],
                'brand' => $shopInfo['brand'],
                'platforms' => [
                    'grab' => ['is_online' => null, 'last_checked' => null],
                    'foodpanda' => ['is_online' => null, 'last_checked' => null],
                    'deliveroo' => ['is_online' => null, 'last_checked' => null],
                ],
                'online_count' => 0,
                'offline_count' => 0,
            ];
        }

        $allStores[$status->shop_id]['platforms'][$status->platform] = [
            'is_online' => (bool) $status->is_online,
            'last_checked' => $status->last_checked_at ? \Carbon\Carbon::parse($status->last_checked_at)->diffForHumans() : 'Never',
            'last_checked_full' => $status->last_checked_at,
            'status' => $status->last_check_status ?? 'unknown',
        ];
    }

    // Calculate online/offline counts for each store
    foreach ($allStores as $shopId => &$store) {
        $onlineCount = 0;
        $offlineCount = 0;

        foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
            if (isset($store['platforms'][$platform]['is_online'])) {
                if ($store['platforms'][$platform]['is_online']) {
                    $onlineCount++;
                } else {
                    $offlineCount++;
                }
            }
        }

        $store['online_count'] = $onlineCount;
        $store['offline_count'] = $offlineCount;

        // Determine overall status
        if ($onlineCount === 3) {
            $store['overall_status'] = 'all_online';
        } elseif ($offlineCount === 3) {
            $store['overall_status'] = 'all_offline';
        } else {
            $store['overall_status'] = 'mixed';
        }
    }

    // Calculate global statistics
    $totalStores = count($allStores);
    $storesAllOnline = collect($allStores)->where('overall_status', 'all_online')->count();
    $storesAllOffline = collect($allStores)->where('overall_status', 'all_offline')->count();
    $storesMixed = collect($allStores)->where('overall_status', 'mixed')->count();

    $totalPlatforms = $platformStatuses->count();
    $onlinePlatforms = $platformStatuses->where('is_online', 1)->count();
    $offlinePlatforms = $totalPlatforms - $onlinePlatforms;

    $lastScrapeTime = DB::table('platform_status')->max('last_checked_at');

    return view('offline-items', [
        'stores' => array_values($allStores),
        'stats' => [
            'total_stores' => $totalStores,
            'all_online' => $storesAllOnline,
            'all_offline' => $storesAllOffline,
            'mixed' => $storesMixed,
            'total_platforms' => $totalPlatforms,
            'online_platforms' => $onlinePlatforms,
            'offline_platforms' => $offlinePlatforms,
        ],
        'lastScrape' => $lastScrapeTime ? \Carbon\Carbon::parse($lastScrapeTime)->timezone('Asia/Singapore')->format('M j, Y h:i A') . ' SGT' : 'Never',
        'lastScrapeAgo' => $lastScrapeTime ? \Carbon\Carbon::parse($lastScrapeTime)->diffForHumans() : 'Never',
    ]);
});

// View Logs: Status History Timeline with Cards
Route::get('/store/{shopId}/logs', function ($shopId) {
    $shopMap = ShopHelper::getShopMap();
    $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown Store', 'brand' => 'Unknown Brand'];

    // Get current platform status
    $platformStatus = DB::table('platform_status')
        ->where('shop_id', $shopId)
        ->get()
        ->keyBy('platform');

    $platformData = [];
    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
        $status = $platformStatus->get($platform);
        $offlineItems = DB::table('items')
            ->where('shop_name', $shopInfo['name'])
            ->where('platform', $platform)
            ->where('is_available', 0)
            ->get();

        $platformData[$platform] = [
            'name' => ucfirst($platform),
            'status' => $status && $status->is_online ? 'Online' : 'Offline',
            'last_checked' => $status ? $status->last_checked_at : null,
            'offline_items' => $offlineItems,
            'offline_count' => $offlineItems->count(),
        ];
    }

    $onlinePlatforms = count(array_filter($platformData, fn($d) => $d['status'] === 'Online'));
    $totalOffline = array_sum(array_column($platformData, 'offline_count'));

    // Check if we need to create a new log entry for today
    $nowSgt = \Carbon\Carbon::now('Asia/Singapore');
    $today = $nowSgt->copy()->startOfDay();

    $existingLog = DB::table('store_status_logs')
        ->where('shop_id', $shopId)
        ->whereDate('logged_at', $today)
        ->first();

    if (!$existingLog) {
        // Create a new log entry for today
        DB::table('store_status_logs')->insert([
            'shop_id' => $shopId,
            'shop_name' => $shopInfo['name'],
            'platforms_online' => $onlinePlatforms,
            'total_platforms' => 3,
            'total_offline_items' => $totalOffline,
            'platform_data' => json_encode($platformData),
            'logged_at' => $nowSgt,
            'created_at' => $nowSgt,
            'updated_at' => $nowSgt,
        ]);
    } else {
        // Update existing log with current time and status
        DB::table('store_status_logs')
            ->where('id', $existingLog->id)
            ->update([
                'platforms_online' => $onlinePlatforms,
                'total_offline_items' => $totalOffline,
                'platform_data' => json_encode($platformData),
                'logged_at' => $nowSgt,
                'updated_at' => $nowSgt,
            ]);
    }

    // Get all historical logs for this store (newest first)
    $historicalLogs = DB::table('store_status_logs')
        ->where('shop_id', $shopId)
        ->orderBy('logged_at', 'desc')
        ->get();

    $statusCards = [];
    foreach ($historicalLogs as $index => $log) {
        $loggedAt = \Carbon\Carbon::parse($log->logged_at)->timezone('Asia/Singapore');
        $platformDataDecoded = json_decode($log->platform_data, true);

        // For today's entry, always use current time
        $isToday = $loggedAt->isToday();
        $displayTime = $isToday ? $nowSgt : $loggedAt;

        $statusCards[] = [
            'id' => $historicalLogs->count() - $index, // Reverse numbering (newest = highest number)
            'timestamp' => $displayTime,
            'outlet_status' => $log->platforms_online === 3 ? 'All Online' : ($log->platforms_online === 0 ? 'All Offline' : 'Mixed'),
            'platforms_online' => $log->platforms_online,
            'total_offline_items' => $log->total_offline_items,
            'platform_data' => $platformDataDecoded,
            'is_current' => $index === 0, // First item is most recent
        ];
    }

    return view('store-logs', [
        'shopId' => $shopId,
        'shopName' => $shopInfo['name'],
        'brandName' => $shopInfo['brand'],
        'statusCards' => $statusCards,
    ]);
});

// Export Dashboard Overview to CSV
Route::get('/dashboard/export', function () {
    $shopMap = ShopHelper::getShopMap();

    // Get all stores with platform status
    $shopIdsWithPlatforms = DB::table('platform_status')
        ->distinct('shop_id')
        ->pluck('shop_id');

    $exportData = [];

    foreach ($shopIdsWithPlatforms as $shopId) {
        $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];

        // Get platform status for this shop
        $platformStatus = DB::table('platform_status')
            ->where('shop_id', $shopId)
            ->get()
            ->keyBy('platform');

        // Get offline items per platform
        $grabOffline = DB::table('items')
            ->where('shop_name', $shopInfo['name'])
            ->where('platform', 'grab')
            ->where('is_available', 0)
            ->count();

        $foodpandaOffline = DB::table('items')
            ->where('shop_name', $shopInfo['name'])
            ->where('platform', 'foodpanda')
            ->where('is_available', 0)
            ->count();

        $deliverooOffline = DB::table('items')
            ->where('shop_name', $shopInfo['name'])
            ->where('platform', 'deliveroo')
            ->where('is_available', 0)
            ->count();

        $totalOffline = $grabOffline + $foodpandaOffline + $deliverooOffline;

        // Platform status details
        $grabStatus = $platformStatus->get('grab');
        $foodpandaStatus = $platformStatus->get('foodpanda');
        $deliverooStatus = $platformStatus->get('deliveroo');

        // Calculate overall status
        $onlineCount = 0;
        if ($grabStatus && $grabStatus->is_online) $onlineCount++;
        if ($foodpandaStatus && $foodpandaStatus->is_online) $onlineCount++;
        if ($deliverooStatus && $deliverooStatus->is_online) $onlineCount++;

        $overallStatus = 'Mixed';
        if ($onlineCount === 3) $overallStatus = 'All Online';
        if ($onlineCount === 0) $overallStatus = 'All Offline';

        $exportData[] = [
            'brand' => $shopInfo['brand'],
            'store_name' => $shopInfo['name'],
            'shop_id' => $shopId,
            'overall_status' => $overallStatus,
            'platforms_online' => $onlineCount . '/3',
            'total_offline_items' => $totalOffline,

            // Grab details
            'grab_status' => $grabStatus ? ($grabStatus->is_online ? 'Online' : 'OFFLINE') : 'Unknown',
            'grab_offline_items' => $grabOffline,
            'grab_last_checked' => $grabStatus && $grabStatus->last_checked_at ? \Carbon\Carbon::parse($grabStatus->last_checked_at)->format('Y-m-d H:i:s') : 'Never',

            // FoodPanda details
            'foodpanda_status' => $foodpandaStatus ? ($foodpandaStatus->is_online ? 'Online' : 'OFFLINE') : 'Unknown',
            'foodpanda_offline_items' => $foodpandaOffline,
            'foodpanda_last_checked' => $foodpandaStatus && $foodpandaStatus->last_checked_at ? \Carbon\Carbon::parse($foodpandaStatus->last_checked_at)->format('Y-m-d H:i:s') : 'Never',

            // Deliveroo details
            'deliveroo_status' => $deliverooStatus ? ($deliverooStatus->is_online ? 'Online' : 'OFFLINE') : 'Unknown',
            'deliveroo_offline_items' => $deliverooOffline,
            'deliveroo_last_checked' => $deliverooStatus && $deliverooStatus->last_checked_at ? \Carbon\Carbon::parse($deliverooStatus->last_checked_at)->format('Y-m-d H:i:s') : 'Never',
        ];
    }

    // Generate CSV filename with timestamp
    $filename = 'hawkerops_dashboard_' . date('Y-m-d_His') . '.csv';

    // Set headers for CSV download
    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    // Create CSV content
    $callback = function() use ($exportData) {
        $file = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 support
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Headers
        fputcsv($file, [
            'Brand',
            'Store Name',
            'Shop ID',
            'Overall Status',
            'Platforms Online',
            'Total Offline Items',

            'Grab Status',
            'Grab Offline Items',
            'Grab Last Checked',

            'FoodPanda Status',
            'FoodPanda Offline Items',
            'FoodPanda Last Checked',

            'Deliveroo Status',
            'Deliveroo Offline Items',
            'Deliveroo Last Checked',
        ]);

        // CSV Data
        foreach ($exportData as $row) {
            fputcsv($file, [
                $row['brand'],
                $row['store_name'],
                $row['shop_id'],
                $row['overall_status'],
                $row['platforms_online'],
                $row['total_offline_items'],

                $row['grab_status'],
                $row['grab_offline_items'],
                $row['grab_last_checked'],

                $row['foodpanda_status'],
                $row['foodpanda_offline_items'],
                $row['foodpanda_last_checked'],

                $row['deliveroo_status'],
                $row['deliveroo_offline_items'],
                $row['deliveroo_last_checked'],
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
});

// Export Logs to CSV
Route::get('/store/{shopId}/logs/export', function ($shopId) {
    $shopMap = ShopHelper::getShopMap();
    $shopInfo = $shopMap[$shopId] ?? ['name' => 'Unknown Store', 'brand' => 'Unknown Brand'];

    // Get all history events for this store, ordered by most recent first
    $history = DB::table('item_status_history')
        ->where('shop_id', $shopId)
        ->orderBy('changed_at', 'desc')
        ->get();

    // Generate CSV filename with timestamp
    $filename = 'status_history_' . $shopId . '_' . date('Y-m-d_His') . '.csv';

    // Set headers for CSV download
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    // Create CSV content
    $callback = function() use ($history, $shopInfo) {
        $file = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 support
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Headers
        fputcsv($file, [
            'Date',
            'Time',
            'Item Name',
            'Shop Name',
            'Platform',
            'Status',
            'Category',
            'Price',
            'Changed At (Full Timestamp)',
        ]);

        // CSV Data
        foreach ($history as $event) {
            $changedAt = \Carbon\Carbon::parse($event->changed_at);

            fputcsv($file, [
                $changedAt->format('Y-m-d'),
                $changedAt->format('H:i:s'),
                $event->item_name,
                $event->shop_name,
                ucfirst($event->platform),
                $event->is_available ? 'Online' : 'Offline',
                $event->category ?? 'N/A',
                $event->price ? '$' . number_format($event->price, 2) : 'N/A',
                $changedAt->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
});

// MOCK: Items Page (for preview before implementing real scraper)
Route::get('/items-mock', function () {
    // Load mock data from JSON file
    $mockDataPath = base_path('mock_items_data.json');

    if (!file_exists($mockDataPath)) {
        return response()->json(['error' => 'Mock data file not found'], 404);
    }

    $mockData = json_decode(file_get_contents($mockDataPath), true);
    $items = $mockData['items'] ?? [];

    return view('items-mock', [
        'items' => $items,
    ]);
});

// ========== NEW PAGES: ALERTS, REPORTS, SETTINGS ==========

// Alerts Page
Route::get('/alerts', function () {
    // Mock alert data - will be replaced with real data later
    $alerts = [
        [
            'type' => 'critical',
            'title' => 'Platform Down: FoodPanda',
            'message' => '3 stores are currently offline on FoodPanda platform',
            'time' => '5 minutes ago',
            'store' => 'Multiple stores',
        ],
        [
            'type' => 'warning',
            'title' => 'High Offline Items',
            'message' => 'McDonald\'s Jurong Point has 25 items offline',
            'time' => '12 minutes ago',
            'store' => 'McDonald\'s Jurong Point',
        ],
    ];

    $stats = [
        'critical' => 1,
        'warnings' => 1,
        'info' => 0,
        'resolved' => 5,
    ];

    return view('alerts', [
        'alerts' => $alerts,
        'stats' => $stats,
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Reports: Daily Trends
Route::get('/reports/daily-trends', function () {
    $trends = [
        'avg_uptime' => '98.5',
        'avg_offline' => '12',
        'peak_hour' => '2 PM',
        'incidents' => '8',
    ];

    return view('reports.daily-trends', [
        'trends' => $trends,
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Reports: Platform Reliability
Route::get('/reports/platform-reliability', function () {
    return view('reports.platform-reliability', [
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Reports: Item Performance
Route::get('/reports/item-performance', function () {
    $itemStats = [
        'total' => '2,450',
        'frequent_offline' => '47',
        'always_on' => '2,103',
        'sometimes_off' => '300',
    ];

    return view('reports.item-performance', [
        'itemStats' => $itemStats,
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Reports: Store Comparison
Route::get('/reports/store-comparison', function () {
    return view('reports.store-comparison', [
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Settings: Scraper Status
Route::get('/settings/scraper-status', function () {
    return view('settings.scraper-status', [
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Settings: Configuration
Route::get('/settings/configuration', function () {
    return view('settings.configuration', [
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});

// Settings: Export Data
Route::get('/settings/export', function () {
    return view('settings.export', [
        'lastSync' => \Carbon\Carbon::now('Asia/Singapore')->format('M j, Y h:i A') . ' SGT',
    ]);
});
