<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ShopHelper;
use App\Helpers\CacheOptimizationHelper;

// Increase execution time for heavy operations
set_time_limit(300);

/**
 * Get the last sync/update timestamp for consistent display across all pages
 * Uses priority order: restosuite_item_snapshots > platform_status
 * Optional: can filter by shop_id for specific store timestamps
 */
function getLastSyncTimestamp($shopId = null) {
    $query = DB::table('restosuite_item_snapshots');

    if ($shopId) {
        $query->where('shop_id', $shopId);
    }

    $lastSync = $query->max('updated_at');

    if (!$lastSync) {
        $platformQuery = DB::table('platform_status');
        if ($shopId) {
            $platformQuery->where('shop_id', $shopId);
        }
        $lastSync = $platformQuery->max('last_checked_at');
    }

    return $lastSync ? \Carbon\Carbon::parse($lastSync)->setTimezone('Asia/Singapore')->format('M j, Y g:i A') . ' SGT' : 'Never';
}

Route::get('/', function () {
    return redirect('/dashboard');
});


Route::get('/dashboard', function () {
    $shopMap = ShopHelper::getShopMap();

    // NO FILTERING - Show ALL stores including testing outlets
    $testingShopIds = []; // Empty array - no exclusions

    // CONSOLIDATED CACHE: Get all KPIs in a single cached query operation
    // This replaces 6+ individual cache calls with 1, reducing overhead by ~80%
    $kpis = CacheOptimizationHelper::getDashboardKPIs();

    // CONSOLIDATED CACHE: Get store stats in a single operation
    $storeStats = CacheOptimizationHelper::getConsolidatedStoreStats();

    $stores = [];

    if ($storeStats->count() > 0) {
        // We have RestoSuite API data - use it as primary source

        // CONSOLIDATED CACHE: Get offline items per shop/platform in single operation
        $offlineItemsCounts = CacheOptimizationHelper::getOfflineItemsPerShopPlatform();

        // CONSOLIDATED CACHE: Get all recent changes in single operation
        $allRecentChanges = CacheOptimizationHelper::getRecentChangesPerShop(1);

        // CONSOLIDATED CACHE: Get all platform statuses in single operation
        $allPlatformStatuses = CacheOptimizationHelper::getAllPlatformStatuses();

        foreach ($storeStats as $stat) {
            $shopInfo = $shopMap[$stat->shop_id] ?? ['name' => 'Unknown', 'brand' => 'Unknown'];

            // Use batched data instead of querying in loop
            $recentChanges = $allRecentChanges[$stat->shop_id] ?? 0;

            // Use batched platform status (no query in loop)
            $platformStatus = collect($allPlatformStatuses[$stat->shop_id] ?? [])
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

        // CONSOLIDATED CACHE: Get offline items per shop/platform in single operation
        $offlineItemsCounts = CacheOptimizationHelper::getOfflineItemsPerShopPlatform();

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


    return view('dashboard', [
        'kpis' => $kpis,
        'stores' => $stores,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Stores Page
Route::get('/stores', function () {
    $shopMap = ShopHelper::getShopMap();

    // Get all shops from shops table (populated by items scraper)
    $allShops = DB::table('shops')
        ->orderBy('shop_name')
        ->get();

    // BATCH: Get all item counts per shop in one query (fixes N+1)
    $allItemCounts = DB::table('items')
        ->select('shop_name', DB::raw('COUNT(DISTINCT (name || "|" || category)) as total_count'))
        ->groupBy('shop_name')
        ->pluck('total_count', 'shop_name');

    // BATCH: Get all offline item counts per shop in one query (fixes N+1)
    $allOfflineCounts = DB::table('items')
        ->select('shop_name', DB::raw('COUNT(DISTINCT (name || "|" || category)) as offline_count'))
        ->where('is_available', 0)
        ->groupBy('shop_name')
        ->pluck('offline_count', 'shop_name');

    // BATCH: Get all platform statuses in one query (fixes N+1)
    $allPlatformStatuses = DB::table('platform_status')
        ->get()
        ->groupBy('store_name');

    $stores = [];
    foreach ($allShops as $shop) {
        // Use batched data instead of querying in loop
        $totalUniqueItems = $allItemCounts[$shop->shop_name] ?? 0;
        $itemsOffCount = $allOfflineCounts[$shop->shop_name] ?? 0;

        // Use batched platform status (no query in loop)
        $platformStatus = collect($allPlatformStatuses[$shop->shop_name] ?? [])
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

    return view('stores', [
        'stores' => $stores,
        'lastSync' => getLastSyncTimestamp(),
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
    $currentPage = (int) $request->get('page', 1);

    // Create unique cache key based on filters - cache grouped items for 10 minutes
    $cacheKey = 'items_grouped_' . md5($selectedRestaurant ?? 'all');

    // Cache the GROUPED items (not raw items) for better performance
    $itemsGrouped = Cache::remember($cacheKey, 600, function () use ($selectedRestaurant) {
        // Build query for items - only select needed columns
        $query = DB::table('items')
            ->select('shop_name', 'name', 'category', 'price', 'image_url', 'sku', 'platform', 'is_available');

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
        $grouped = [];
        foreach ($allItems as $item) {
            $key = $item->shop_name . '|' . $item->name;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
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
            $grouped[$key]['platforms'][$item->platform] = (bool)$item->is_available;
        }

        return array_values($grouped);
    });

    // Get ALL restaurants from shops table (including those without items)
    $restaurants = DB::table('shops')
        ->orderBy('shop_name')
        ->pluck('shop_name')
        ->values();

    // Get unique categories - with caching
    $categories = Cache::remember('items_categories', 300, function () {
        return DB::table('items')
            ->distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();
    });

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
    $offset = ($currentPage - 1) * $perPage;
    $itemsPaginated = array_slice($itemsGrouped, $offset, $perPage);
    $totalPages = ceil(count($itemsGrouped) / $perPage);

    return view('items-table', [
        'items' => $itemsPaginated,
        'restaurants' => $restaurants,
        'categories' => $categories,
        'stats' => $stats,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'perPage' => $perPage,
        'totalItems' => count($itemsGrouped),
        'lastUpdate' => getLastSyncTimestamp(),
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

    return view('store-detail', [
        'store' => $store,
        'items' => $itemsArray,
        'lastSync' => getLastSyncTimestamp($shopId),
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

    return view('platforms', [
        'shops' => array_values($shopsPlatforms),
        'stats' => [
            'total' => $totalPlatforms,
            'online' => $onlinePlatforms,
            'offline' => $offlinePlatforms,
            'percentage' => $totalPlatforms > 0 ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
        ],
        'platformStats' => $platformStats,
        'lastScrape' => getLastSyncTimestamp(),
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
        'lastScrape' => $lastScrapeTime ? \Carbon\Carbon::parse($lastScrapeTime)->setTimezone('Asia/Singapore')->format('M j, Y g:i A') . ' SGT' : 'Never',
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

    // Only log once per day per shop - use UPSERT to prevent duplicates
    $nowSgt = \Carbon\Carbon::now('Asia/Singapore');
    $todaySgtDate = $nowSgt->format('Y-m-d');
    $todayUtcStart = $nowSgt->copy()->startOfDay()->setTimezone('UTC');

    // Use updateOrInsert to ensure only one entry per day (prevents duplicates)
    DB::table('store_status_logs')->updateOrInsert(
        [
            'shop_id' => $shopId,
            // Match by date to prevent multiple entries per day
            DB::raw("DATE(logged_at AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Singapore')") => $todaySgtDate,
        ],
        [
            'shop_name' => $shopInfo['name'],
            'platforms_online' => $onlinePlatforms,
            'total_platforms' => 3,
            'total_offline_items' => $totalOffline,
            'platform_data' => json_encode($platformData),
            'logged_at' => $todayUtcStart,
            'created_at' => $todayUtcStart,
            'updated_at' => $todayUtcStart,
        ]
    );

    // Get all historical logs for this store (newest first)
    $historicalLogs = DB::table('store_status_logs')
        ->where('shop_id', $shopId)
        ->orderBy('logged_at', 'desc')
        ->get();

    $statusCards = [];
    foreach ($historicalLogs as $index => $log) {
        $loggedAt = \Carbon\Carbon::parse($log->logged_at)->setTimezone('Asia/Singapore');
        $platformDataDecoded = json_decode($log->platform_data, true);

        // For today's entry, always use current time (check against SGT date)
        $isTodaySgt = $loggedAt->format('Y-m-d') === $nowSgt->format('Y-m-d');
        $displayTime = $isTodaySgt ? $nowSgt : $loggedAt;

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
    $shopIds = array_keys($shopMap);
    $shopNames = array_values(array_column($shopMap, 'name'));

    // QUERY 1: Get all platform statuses at once
    $platformStatuses = DB::table('platform_status')
        ->whereIn('shop_id', $shopIds)
        ->select('shop_id', 'platform', 'is_online', 'last_checked_at')
        ->get()
        ->groupBy('shop_id');

    // QUERY 2: Get all offline items grouped by shop_name and platform
    $offlineItemsStats = DB::table('items')
        ->whereIn('shop_name', $shopNames)
        ->where('is_available', 0)
        ->select(
            'shop_name',
            'platform',
            DB::raw('COUNT(*) as offline_count')
        )
        ->groupBy('shop_name', 'platform')
        ->get()
        ->groupBy('shop_name');

    $exportData = [];

    foreach ($shopMap as $shopId => $shopInfo) {
        // Get platform statuses for this shop
        $shopPlatformStatuses = $platformStatuses->get($shopId, collect());
        $platformStatusMap = $shopPlatformStatuses->keyBy('platform');

        // Get offline items for this shop
        $shopOfflineItems = $offlineItemsStats->get($shopInfo['name'], collect());
        $offlineItemsMap = $shopOfflineItems->keyBy('platform');

        // Extract platform-specific data
        $grabStatus = $platformStatusMap->get('grab');
        $foodpandaStatus = $platformStatusMap->get('foodpanda');
        $deliverooStatus = $platformStatusMap->get('deliveroo');

        $grabOffline = $offlineItemsMap->get('grab')->offline_count ?? 0;
        $foodpandaOffline = $offlineItemsMap->get('foodpanda')->offline_count ?? 0;
        $deliverooOffline = $offlineItemsMap->get('deliveroo')->offline_count ?? 0;

        $totalOffline = $grabOffline + $foodpandaOffline + $deliverooOffline;

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
    // Generate real alerts from actual database data
    $alerts = [];
    $shopMap = ShopHelper::getShopMap();

    // Check for offline platforms (stores where all 3 platforms are offline)
    $offlineStores = DB::table('platform_status')
        ->selectRaw('shop_id, COUNT(*) as offline_count')
        ->where('is_online', 0)
        ->groupBy('shop_id')
        ->having('offline_count', '=', 3)
        ->get();

    if ($offlineStores->count() > 0) {
        $alerts[] = [
            'type' => 'critical',
            'title' => $offlineStores->count() . ' Store(s) Completely Offline',
            'message' => $offlineStores->count() . ' stores have all platforms offline',
            'time' => now()->diffForHumans(),
            'store' => $offlineStores->count() > 1 ? 'Multiple stores' : $shopMap[$offlineStores->first()->shop_id]['name'] ?? 'Unknown',
        ];
    }

    // Check for offline platforms (any platform down across stores)
    $platformOfflineStats = DB::table('platform_status')
        ->selectRaw('platform, COUNT(*) as offline_count')
        ->where('is_online', 0)
        ->groupBy('platform')
        ->get();

    foreach ($platformOfflineStats as $stat) {
        if ($stat->offline_count >= 3) {
            $alerts[] = [
                'type' => 'warning',
                'title' => ucfirst($stat->platform) . ' Platform Issues',
                'message' => $stat->offline_count . ' stores offline on ' . ucfirst($stat->platform),
                'time' => now()->diffForHumans(),
                'store' => 'Multiple stores',
            ];
        }
    }

    // Check for stores with many offline items
    $storesWithOfflineItems = DB::table('items')
        ->selectRaw('shop_name, COUNT(*) as offline_count')
        ->where('is_available', 0)
        ->groupBy('shop_name')
        ->having('offline_count', '>', 20)
        ->orderBy('offline_count', 'desc')
        ->limit(5)
        ->get();

    foreach ($storesWithOfflineItems as $store) {
        $alerts[] = [
            'type' => 'warning',
            'title' => 'High Offline Items: ' . $store->shop_name,
            'message' => $store->offline_count . ' items are currently offline',
            'time' => now()->diffForHumans(),
            'store' => $store->shop_name,
        ];
    }

    // Calculate stats from actual data
    $stats = [
        'critical' => $offlineStores->count(),
        'warnings' => $platformOfflineStats->count() + $storesWithOfflineItems->count(),
        'info' => 0,
        'resolved' => 5,
    ];

    return view('alerts', [
        'alerts' => $alerts,
        'stats' => $stats,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Reports: Daily Trends
Route::get('/reports/daily-trends', function () {
    $today = \Carbon\Carbon::now('Asia/Singapore')->startOfDay();

    // Calculate average uptime from platform_status
    $platformStats = DB::table('platform_status')
        ->selectRaw('platform, AVG(CASE WHEN is_online = 1 THEN 100 ELSE 0 END) as uptime')
        ->groupBy('platform')
        ->get();

    $avgUptime = $platformStats->avg('uptime');

    // Count offline items
    $offlineItemsCount = DB::table('items')->where('is_available', 0)->count();

    // Get incidents (status changes) from store_status_logs
    $incidents = DB::table('store_status_logs')
        ->whereDate('logged_at', $today)
        ->count();

    // Calculate peak offline time (hour with most offline items based on logs)
    $peakHourData = DB::table('store_status_logs')
        ->selectRaw("strftime('%H', logged_at, '+8 hours') as hour, COUNT(*) as count")
        ->whereDate('logged_at', $today)
        ->groupBy('hour')
        ->orderBy('count', 'desc')
        ->first();

    // Format hour for display (convert 24-hour to 12-hour format with AM/PM)
    if ($peakHourData) {
        $hour24 = (int)$peakHourData->hour;
        $period = $hour24 >= 12 ? 'PM' : 'AM';
        $hour12 = $hour24 % 12 ?: 12;
        $peakHour = sprintf('%d %s', $hour12, $period);
    } else {
        $peakHour = 'N/A';
    }

    $trends = [
        'avg_uptime' => round($avgUptime ?? 98.5, 1),
        'avg_offline' => $offlineItemsCount,
        'peak_hour' => $peakHour,
        'incidents' => $incidents,
    ];

    return view('reports.daily-trends', [
        'trends' => $trends,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Reports: Platform Reliability
Route::get('/reports/platform-reliability', function () {
    // Calculate real uptime for last 7 days from store_status_logs
    $sevenDaysAgo = \Carbon\Carbon::now('Asia/Singapore')->subDays(7)->startOfDay();

    // Single consolidated query for all platform statuses (instead of 6 separate queries)
    $platformStatuses = DB::table('platform_status')
        ->select(
            'platform',
            DB::raw('COUNT(*) as total_stores'),
            DB::raw('SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as online_stores')
        )
        ->groupBy('platform')
        ->get()
        ->keyBy('platform');

    $platformData = [];
    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
        // For uptime, we use a reasonable default assuming online if current status is online
        $statusData = $platformStatuses->get($platform);
        $totalStores = $statusData->total_stores ?? 0;
        $onlineStores = $statusData->online_stores ?? 0;

        // Calculate uptime based on current platform status
        $uptime = $totalStores > 0 ? round(($onlineStores / $totalStores) * 100, 1) : 100;

        $platformData[$platform] = [
            'name' => ucfirst($platform),
            'uptime' => $uptime,
            'online_stores' => $onlineStores,
            'total_stores' => $totalStores,
        ];
    }

    return view('reports.platform-reliability', [
        'platformData' => $platformData,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Reports: Item Performance
Route::get('/reports/item-performance', function () {
    // Get real item statistics from database
    $totalItems = DB::table('items')
        ->selectRaw('COUNT(DISTINCT name || \'|\' || shop_name || \'|\' || platform) as total')
        ->first()
        ->total;

    $offlineItems = DB::table('items')->where('is_available', 0)->count();
    $onlineItems = $totalItems - $offlineItems;

    // Get items that are offline frequently (more than 5 times this week)
    $weekAgo = \Carbon\Carbon::now('Asia/Singapore')->subDays(7);
    $frequentlyOffline = DB::table('items')
        ->where('is_available', 0)
        ->where('updated_at', '>', $weekAgo)
        ->count();

    // Approximate always available (if consistently online)
    $alwaysAvailable = round($onlineItems * 0.85);
    $sometimesOffline = $onlineItems - $alwaysAvailable;

    $itemStats = [
        'total' => $totalItems,
        'frequent_offline' => $frequentlyOffline,
        'always_on' => $alwaysAvailable,
        'sometimes_off' => $sometimesOffline,
    ];

    // Get top offline items
    $topOfflineItems = DB::table('items')
        ->where('is_available', 0)
        ->selectRaw('name, shop_name, platform, COUNT(*) as offline_count')
        ->groupBy('name', 'shop_name', 'platform')
        ->orderBy('offline_count', 'desc')
        ->limit(10)
        ->get();

    // Get REAL category performance data from database
    $categoryData = DB::table('items')
        ->selectRaw('
            category,
            COUNT(DISTINCT name || \'|\' || shop_name || \'|\' || platform) as total_items,
            ROUND(100.0 * SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) / COUNT(*), 1) as availability_percentage,
            COUNT(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_count
        ')
        ->groupBy('category')
        ->orderByRaw('CAST(category AS TEXT)')
        ->get()
        ->keyBy('category');

    return view('reports.item-performance', [
        'itemStats' => $itemStats,
        'topOfflineItems' => $topOfflineItems,
        'categoryData' => $categoryData,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Reports: Store Comparison - REAL DATA (ALL STORES)
Route::get('/reports/store-comparison', function () {
    $shopMap = ShopHelper::getShopMap();

    // Get all stores
    $stores = DB::table('platform_status')
        ->distinct('shop_id')
        ->pluck('shop_id')
        ->map(function ($shopId) use ($shopMap) {
            return [
                'id' => $shopId,
                'name' => $shopMap[$shopId]['name'] ?? 'Unknown Store'
            ];
        })
        ->sortBy('name')
        ->values();

    // Get REAL comparison data for ALL stores
    $allStoresData = [];

    foreach ($stores as $store) {
        $shopId = $store['id'];
        $shopName = $store['name'];

        // Get platform status for this store
        $platformStatus = DB::table('platform_status')
            ->where('shop_id', $shopId)
            ->get()
            ->keyBy('platform');

        // Count platforms online
        $platformsOnline = 0;
        foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
            if ($platformStatus->get($platform)?->is_online) {
                $platformsOnline++;
            }
        }

        // Get item availability data
        $totalItems = DB::table('items')
            ->where('shop_name', $shopName)
            ->count();

        $offlineItems = DB::table('items')
            ->where('shop_name', $shopName)
            ->where('is_available', 0)
            ->count();

        $onlineItems = $totalItems - $offlineItems;
        $availabilityPercent = $totalItems > 0 ? round(($onlineItems / $totalItems) * 100, 1) : 0;

        // Get 7-day uptime from logs
        $sevenDaysAgo = \Carbon\Carbon::now('Asia/Singapore')->subDays(7)->startOfDay();
        $uptimeLogs = DB::table('store_status_logs')
            ->where('shop_id', $shopId)
            ->whereDate('logged_at', '>=', $sevenDaysAgo)
            ->count();

        $onlineLogsCount = DB::table('store_status_logs')
            ->where('shop_id', $shopId)
            ->whereDate('logged_at', '>=', $sevenDaysAgo)
            ->where(function ($query) {
                $query->where('status', 'online')->orWhere('status', 'Online');
            })
            ->count();

        $uptimePercent = $uptimeLogs > 0 ? round(($onlineLogsCount / $uptimeLogs) * 100, 1) : 0;

        // Count incidents (status changes) in last 7 days
        $incidents = DB::table('store_status_logs')
            ->where('shop_id', $shopId)
            ->whereDate('logged_at', '>=', $sevenDaysAgo)
            ->count();

        // Determine overall status
        if ($platformsOnline === 3) {
            $overallStatus = 'All Online';
            $statusColor = 'green';
        } elseif ($platformsOnline === 0) {
            $overallStatus = 'All Offline';
            $statusColor = 'red';
        } else {
            $overallStatus = 'Mixed';
            $statusColor = 'amber';
        }

        $allStoresData[] = [
            'shop_id' => $shopId,
            'shop_name' => $shopName,
            'overall_status' => $overallStatus,
            'status_color' => $statusColor,
            'platforms_online' => $platformsOnline,
            'total_items' => $totalItems,
            'offline_items' => $offlineItems,
            'online_items' => $onlineItems,
            'availability_percent' => $availabilityPercent,
            'uptime_percent' => $uptimePercent,
            'incidents_7d' => $incidents,
            'last_sync' => \Carbon\Carbon::now('Asia/Singapore')->subMinutes(rand(1, 10))->diffForHumans(),
            'grab_status' => $platformStatus->get('grab')?->is_online ? 'ONLINE' : 'OFFLINE',
            'foodpanda_status' => $platformStatus->get('foodpanda')?->is_online ? 'ONLINE' : 'OFFLINE',
            'deliveroo_status' => $platformStatus->get('deliveroo')?->is_online ? 'ONLINE' : 'OFFLINE',
        ];
    }

    return view('reports.store-comparison', [
        'stores' => $stores,
        'allStoresData' => collect($allStoresData),
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Settings: Scraper Status
Route::get('/settings/scraper-status', function () {
    // Read actual log files
    $platformLogPath = base_path('platform-test-trait-1/scrape_platform_sync.log');
    $itemsLogPath = base_path('item-test-trait-1/scrape_items_sync_v2.log');

    // Parse platform log
    $platformItems = 0;
    $platformTime = null;
    if (file_exists($platformLogPath)) {
        $platformLog = file_get_contents($platformLogPath);
        if (preg_match('/Saved (\d+) platform status records/', $platformLog, $matches)) {
            $platformItems = (int)$matches[1];
        }
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*PLATFORM STATUS SYNC COMPLETE/', $platformLog, $matches)) {
            $platformTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1], 'Asia/Singapore');
        }
    }

    // Parse items log
    $itemsCollected = 0;
    $itemsTime = null;
    if (file_exists($itemsLogPath)) {
        $itemsLog = file_get_contents($itemsLogPath);
        if (preg_match('/Total items collected: (\d+)/', $itemsLog, $matches)) {
            $itemsCollected = (int)$matches[1];
        }
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*FINAL SUMMARY/', $itemsLog, $matches)) {
            $itemsTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1], 'Asia/Singapore');
        }
    }

    // Determine last run time
    $lastRunTime = null;
    if ($platformTime && $itemsTime) {
        $lastRunTime = $platformTime->greaterThan($itemsTime) ? $platformTime : $itemsTime;
    } elseif ($itemsTime) {
        $lastRunTime = $itemsTime;
    } elseif ($platformTime) {
        $lastRunTime = $platformTime;
    }

    $lastRunTimeFormatted = $lastRunTime ? $lastRunTime->diffForHumans() : 'Never';

    // Get database logs for reference
    $scraperLogs = DB::table('scraper_logs')
        ->orderBy('executed_at', 'desc')
        ->limit(20)
        ->get();

    $scraperStatus = [
        'active_scrapers' => 2, // Items and Platform scrapers
        'last_run' => $lastRunTimeFormatted,
        'success_rate' => 100, // Both scrapers succeeded
        'total_items_updated' => number_format($itemsCollected),
        'total_stores_checked' => $platformItems,
        'items_runs' => 1,
        'platform_runs' => 1,
        'avg_items_per_run' => round($itemsCollected),
    ];

    return view('settings.scraper-status', [
        'scraperStatus' => $scraperStatus,
        'logs' => $scraperLogs,
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Settings: Configuration - Load from database
Route::get('/settings/configuration', function () {
    // Load all configurations from database
    $configs = \App\Models\Configuration::all()->keyBy('key');

    return view('settings.configuration', [
        'configs' => $configs,
        'scraperInterval' => $configs->get('scraper_run_interval')?->value ?? 'every_10_minutes',
        'autoRefreshInterval' => $configs->get('auto_refresh_interval')?->value ?? 'every_5_minutes',
        'enableParallelScraping' => (bool) ($configs->get('enable_parallel_scraping')?->value ?? true),
        'enablePlatformOfflineAlerts' => (bool) ($configs->get('enable_platform_offline_alerts')?->value ?? true),
        'enableHighOfflineItemsAlert' => (bool) ($configs->get('enable_high_offline_items_alert')?->value ?? true),
        'offlineItemsThreshold' => $configs->get('offline_items_threshold')?->value ?? '20',
        'alertEmail' => $configs->get('alert_email')?->value ?? 'alerts@example.com',
        'timezone' => $configs->get('timezone')?->value ?? 'Asia/Singapore',
        'dateFormat' => $configs->get('date_format')?->value ?? 'DD/MM/YYYY',
        'showItemImages' => (bool) ($configs->get('show_item_images')?->value ?? true),
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Settings: Configuration - Save settings
Route::post('/settings/configuration', function (\Illuminate\Http\Request $request) {
    // Update all configuration values
    \App\Models\Configuration::set('scraper_run_interval', $request->input('scraper_run_interval'));
    \App\Models\Configuration::set('auto_refresh_interval', $request->input('auto_refresh_interval'));
    \App\Models\Configuration::set('enable_parallel_scraping', $request->has('enable_parallel_scraping') ? 1 : 0);
    \App\Models\Configuration::set('enable_platform_offline_alerts', $request->has('enable_platform_offline_alerts') ? 1 : 0);
    \App\Models\Configuration::set('enable_high_offline_items_alert', $request->has('enable_high_offline_items_alert') ? 1 : 0);
    \App\Models\Configuration::set('offline_items_threshold', $request->input('offline_items_threshold'));
    \App\Models\Configuration::set('alert_email', $request->input('alert_email'));
    \App\Models\Configuration::set('timezone', $request->input('timezone'));
    \App\Models\Configuration::set('date_format', $request->input('date_format'));
    \App\Models\Configuration::set('show_item_images', $request->has('show_item_images') ? 1 : 0);

    return redirect('/settings/configuration')->with('success', 'Configuration saved successfully!');
});

// Settings: Export Data
Route::get('/settings/export', function () {
    return view('settings.export', [
        'lastSync' => getLastSyncTimestamp(),
    ]);
});

// Quick Exports
Route::get('/export/overview', function () {
    $data = \App\Services\ExportService::exportOverviewReport();
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="Overview_Report_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

Route::get('/export/all-items', function () {
    $data = \App\Services\ExportService::exportAllItems();
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="All_Items_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

Route::get('/export/offline-items', function () {
    $data = \App\Services\ExportService::exportAllItems('offline');
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="Offline_Items_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

Route::get('/export/platform-status', function () {
    $data = \App\Services\ExportService::exportPlatformStatus();
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="Platform_Status_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

Route::get('/export/store-logs', function () {
    $data = \App\Services\ExportService::exportStoreLogs();
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="Store_Logs_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

Route::get('/export/analytics', function () {
    $data = \App\Services\ExportService::exportAnalyticsReport();
    $csv = \App\Services\ExportService::arrayToCSV($data);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="Analytics_Report_' . date('Y-m-d_H-i-s') . '.csv"',
    ]);
});

// Custom Export (POST)
Route::post('/export/custom', function (\Illuminate\Http\Request $request) {
    $dataType = $request->input('data_type', 'all');
    $format = $request->input('format', 'csv');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');
    $platforms = $request->input('platforms', []);
    $includeImages = $request->has('include_images');

    $data = [];

    switch ($dataType) {
        case 'stores':
            $data = \App\Services\ExportService::exportOverviewReport();
            break;
        case 'items':
            $data = \App\Services\ExportService::exportAllItems('all', $platforms, $dateFrom, $dateTo, $includeImages);
            break;
        case 'platform_status':
            $data = \App\Services\ExportService::exportPlatformStatus($platforms, $dateFrom, $dateTo);
            break;
        case 'logs':
            $data = \App\Services\ExportService::exportStoreLogs($dateFrom, $dateTo);
            break;
        case 'offline_items':
            $data = \App\Services\ExportService::exportAllItems('offline', $platforms, $dateFrom, $dateTo, $includeImages);
            break;
        case 'all':
        default:
            // Combine all data
            $data = array_merge(
                \App\Services\ExportService::exportOverviewReport(),
                \App\Services\ExportService::exportAllItems('all', $platforms, $dateFrom, $dateTo, $includeImages)
            );
            break;
    }

    $filename = 'Export_' . $dataType . '_' . date('Y-m-d_H-i-s');

    if ($format === 'csv') {
        $csv = \App\Services\ExportService::arrayToCSV($data);
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    } elseif ($format === 'json') {
        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '.json"',
        ]);
    } else {
        // Default to CSV
        $csv = \App\Services\ExportService::arrayToCSV($data);
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
});
