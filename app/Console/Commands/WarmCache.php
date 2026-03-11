<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\CacheOptimizationHelper;
use App\Helpers\ShopHelper;

/**
 * WarmCache — pre-populates all expensive caches before traffic hits.
 *
 * Run automatically at the end of render-build.sh so every Render deploy
 * starts with warm caches. Without this, the first visitor after each deploy
 * triggers all DB queries cold, causing 6-8s page loads.
 */
class WarmCache extends Command
{
    protected $signature   = 'cache:warm';
    protected $description = 'Pre-warm all application caches to eliminate cold-start DB queries after deploy';

    public function handle(): int
    {
        $this->info('⚡ Warming application caches...');
        $start = microtime(true);

        try {
            // ── Shared base data ─────────────────────────────────────────────
            $this->line('  → shop_map');
            $shopMap = ShopHelper::getShopMap();

            // ── Dashboard caches ─────────────────────────────────────────────
            $this->line('  → dashboard_kpis_consolidated');
            CacheOptimizationHelper::getDashboardKPIs();

            $this->line('  → store_stats_consolidated');
            CacheOptimizationHelper::getConsolidatedStoreStats();

            $this->line('  → offline_items_by_shop_platform');
            CacheOptimizationHelper::getOfflineItemsPerShopPlatform();

            $this->line('  → all_platform_statuses');
            CacheOptimizationHelper::getAllPlatformStatuses();

            $this->line('  → recent_changes_per_shop (1d + 7d)');
            CacheOptimizationHelper::getRecentChangesPerShop(1);
            CacheOptimizationHelper::getRecentChangesPerShop(7);

            $this->line('  → alert_metrics_consolidated');
            CacheOptimizationHelper::getAlertMetrics();

            // ── Stores page cache ─────────────────────────────────────────────
            $this->line('  → stores_page_data');
            Cache::remember('stores_page_data', 300, function () use ($shopMap) {
                $allShops = DB::table('shops')->orderBy('shop_name')->get();

                $allItemCounts = DB::table('items')
                    ->select('shop_name', DB::raw("COUNT(DISTINCT (name || '|' || category)) as total_count"))
                    ->groupBy('shop_name')
                    ->pluck('total_count', 'shop_name');

                $allOfflineCounts = DB::table('items')
                    ->select('shop_name', DB::raw("COUNT(DISTINCT (name || '|' || category)) as offline_count"))
                    ->where('is_available', false)
                    ->groupBy('shop_name')
                    ->pluck('offline_count', 'shop_name');

                $allPlatformStatuses = DB::table('platform_status')
                    ->get()
                    ->groupBy('store_name');

                $stores = [];
                foreach ($allShops as $shop) {
                    $totalUniqueItems = $allItemCounts[$shop->shop_name] ?? 0;
                    $itemsOffCount    = $allOfflineCounts[$shop->shop_name] ?? 0;

                    $platformStatus = collect($allPlatformStatuses[$shop->shop_name] ?? [])
                        ->keyBy('platform');

                    $onlineCount  = 0;
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

                    if ($onlineCount === 3) {
                        $status     = 'all_online';
                        $statusText = 'All Platforms Online';
                    } elseif ($onlineCount === 0) {
                        $status     = 'all_offline';
                        $statusText = 'All Platforms Offline';
                    } else {
                        $status     = 'partial_offline';
                        $statusText = "{$offlineCount}/3 Offline";
                    }

                    $shopInfo = $shopMap[$shop->shop_id] ?? [
                        'name'  => $shop->shop_name,
                        'brand' => $shop->organization_name ?? $shop->shop_name ?? 'Unknown',
                    ];

                    $stores[] = [
                        'brand'             => $shopInfo['brand'],
                        'store'             => $shopInfo['name'],
                        'shop_id'           => $shop->shop_id,
                        'status'            => $status,
                        'status_text'       => $statusText,
                        'platforms_online'  => $onlineCount,
                        'platforms_offline' => $offlineCount,
                        'total_items'       => (int) ($totalUniqueItems ?? 0),
                        'items_off'         => (int) ($itemsOffCount ?? 0),
                        'alerts'            => 0,
                        'last_change'       => $shop->last_synced_at
                            ? \Carbon\Carbon::parse($shop->last_synced_at)->diffForHumans()
                            : '—',
                    ];
                }

                return $stores;
            });

            // ── Platforms page cache ──────────────────────────────────────────
            $this->line('  → platforms_page_data');
            Cache::remember('platforms_page_data', 300, function () use ($shopMap) {
                $testingShopIds = [];
                foreach ($shopMap as $shopId => $info) {
                    if (stripos($info['name'], 'testing') !== false ||
                        stripos($info['name'], 'edge') !== false ||
                        stripos($info['name'], 'depot') !== false) {
                        $testingShopIds[] = $shopId;
                    }
                }

                $platformStatuses = DB::table('platform_status')
                    ->whereNotIn('shop_id', $testingShopIds)
                    ->orderBy('shop_id')->orderBy('platform')
                    ->get();

                $shopsPlatforms = [];
                foreach ($platformStatuses as $status) {
                    if (!isset($shopsPlatforms[$status->shop_id])) {
                        $fallbackName = $status->store_name && $status->store_name !== 'Unknown'
                            ? $status->store_name : $status->shop_id;
                        $shopInfo = $shopMap[$status->shop_id] ?? ['name' => $fallbackName, 'brand' => $fallbackName];
                        $shopsPlatforms[$status->shop_id] = [
                            'shop_id'   => $status->shop_id,
                            'shop_name' => $shopInfo['name'],
                            'brand'     => $shopInfo['brand'],
                            'platforms' => [],
                        ];
                    }
                    $shopsPlatforms[$status->shop_id]['platforms'][$status->platform] = [
                        'is_online'    => (bool) $status->is_online,
                        'items_synced' => $status->items_synced ?? 0,
                        'items_total'  => $status->items_total ?? 0,
                        'last_checked' => $status->last_checked_at
                            ? \Carbon\Carbon::parse($status->last_checked_at)->diffForHumans()
                            : 'Never',
                        'status' => $status->last_check_status ?? 'unknown',
                    ];
                }

                $totalPlatforms   = $platformStatuses->count();
                $onlinePlatforms  = $platformStatuses->where('is_online', true)->count();
                $offlinePlatforms = $totalPlatforms - $onlinePlatforms;

                $stats = [
                    'total'      => $totalPlatforms,
                    'online'     => $onlinePlatforms,
                    'offline'    => $offlinePlatforms,
                    'percentage' => $totalPlatforms > 0
                        ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
                ];

                $platformStats = [];
                foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                    $pd     = $platformStatuses->where('platform', $platform);
                    $total  = $pd->count();
                    $online = $pd->where('is_online', true)->count();
                    $platformStats[$platform] = [
                        'total'      => $total,
                        'online'     => $online,
                        'offline'    => $total - $online,
                        'percentage' => $total > 0 ? round(($online / $total) * 100, 2) : 0,
                    ];
                }

                return [$shopsPlatforms, $stats, $platformStats];
            });

            // ── Items page support caches ─────────────────────────────────────
            $this->line('  → shops_name_list');
            Cache::remember('shops_name_list', 3600, function () {
                return DB::table('shops')
                    ->select('shop_name')->orderBy('shop_name')
                    ->pluck('shop_name')->values();
            });

            $this->line('  → items_categories');
            Cache::remember('items_categories', 300, function () {
                return DB::table('items')
                    ->select('category')->distinct()
                    ->whereNotNull('category')
                    ->pluck('category')->sort()->values();
            });

            // ── Report page caches ────────────────────────────────────────────
            $this->line('  → reports_daily_trends');
            $today = \Carbon\Carbon::now('Asia/Singapore')->startOfDay();
            Cache::remember('reports_daily_trends', 300, function () use ($today) {
                $platformStats = DB::table('platform_status')
                    ->selectRaw('platform, AVG(CASE WHEN is_online = true THEN 100 ELSE 0 END) as uptime')
                    ->groupBy('platform')
                    ->get();
                $avgUptime = $platformStats->avg('uptime');
                $offlineItemsCount = DB::table('items')->where('is_available', false)->count();
                $todaySgt = $today->toDateString();
                $incidents = DB::table('store_status_logs')
                    ->whereRaw("DATE(logged_at + INTERVAL '8 hours') = ?", [$todaySgt])
                    ->count();
                $peakHourData = DB::table('store_status_logs')
                    ->selectRaw("EXTRACT(HOUR FROM logged_at + INTERVAL '8 hours')::int as hour, COUNT(*) as count")
                    ->whereRaw("DATE(logged_at + INTERVAL '8 hours') = ?", [$todaySgt])
                    ->groupBy('hour')
                    ->orderBy('count', 'desc')
                    ->first();
                if ($peakHourData) {
                    $hour24 = (int) $peakHourData->hour;
                    $period = $hour24 >= 12 ? 'PM' : 'AM';
                    $hour12 = $hour24 % 12 ?: 12;
                    $peakHour = sprintf('%d %s', $hour12, $period);
                } else {
                    $peakHour = 'N/A';
                }
                return [
                    'avg_uptime'  => round($avgUptime ?? 0, 1),
                    'avg_offline' => $offlineItemsCount,
                    'peak_hour'   => $peakHour,
                    'incidents'   => $incidents,
                ];
            });

            $this->line('  → reports_platform_reliability');
            Cache::remember('reports_platform_reliability', 300, function () {
                $platformStatuses = DB::table('platform_status')
                    ->select(
                        'platform',
                        DB::raw('COUNT(*) as total_stores'),
                        DB::raw('SUM(CASE WHEN is_online = true THEN 1 ELSE 0 END) as online_stores')
                    )
                    ->groupBy('platform')
                    ->get()
                    ->keyBy('platform');
                $platformData = [];
                foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                    $statusData   = $platformStatuses->get($platform);
                    $totalStores  = $statusData->total_stores ?? 0;
                    $onlineStores = $statusData->online_stores ?? 0;
                    $uptime       = $totalStores > 0 ? round(($onlineStores / $totalStores) * 100, 1) : 100;
                    $platformData[$platform] = [
                        'name'          => ucfirst($platform),
                        'uptime'        => $uptime,
                        'online_stores' => $onlineStores,
                        'total_stores'  => $totalStores,
                    ];
                }
                return $platformData;
            });

            $this->line('  → reports_item_performance_v2');
            Cache::remember('reports_item_performance_v2', 300, function () {
                $totalItems = DB::table('items')
                    ->selectRaw("COUNT(DISTINCT name || '|' || shop_name || '|' || platform) as total")
                    ->first()->total;
                $offlineItems    = DB::table('items')->where('is_available', false)->count();
                $onlineItems     = $totalItems - $offlineItems;
                $weekAgo         = \Carbon\Carbon::now('Asia/Singapore')->subDays(7);
                $frequentlyOffline = DB::table('items')
                    ->where('is_available', false)
                    ->where('updated_at', '>', $weekAgo)
                    ->count();
                $alwaysAvailable  = round($onlineItems * 0.85);
                $sometimesOffline = $onlineItems - $alwaysAvailable;
                $itemStats = [
                    'total'            => $totalItems,
                    'frequent_offline' => $frequentlyOffline,
                    'always_on'        => $alwaysAvailable,
                    'sometimes_off'    => $sometimesOffline,
                ];
                $sevenDaysAgo = \Carbon\Carbon::now('Asia/Singapore')->subDays(7)->toDateTimeString();
                $hasHistory   = DB::table('item_status_history')->where('changed_at', '>=', $sevenDaysAgo)->exists();
                if ($hasHistory) {
                    $topOfflineItems = DB::select("
                        WITH ranked AS (
                            SELECT item_name, shop_name, platform, is_available, changed_at,
                                LEAD(changed_at) OVER (
                                    PARTITION BY item_name, shop_name, platform ORDER BY changed_at
                                ) as next_change
                            FROM item_status_history WHERE changed_at >= :since
                        ),
                        offline_durations AS (
                            SELECT item_name, shop_name, platform,
                                EXTRACT(EPOCH FROM (COALESCE(next_change, NOW()) - changed_at)) / 3600.0 as hours
                            FROM ranked WHERE is_available = false
                        )
                        SELECT item_name as name, shop_name, platform,
                            COUNT(*) as offline_count,
                            ROUND(AVG(hours)::numeric, 1) as avg_hours_offline
                        FROM offline_durations
                        GROUP BY item_name, shop_name, platform
                        ORDER BY offline_count DESC LIMIT 10
                    ", ['since' => $sevenDaysAgo]);
                } else {
                    $topOfflineItems = DB::table('items')
                        ->where('is_available', false)
                        ->selectRaw('name, shop_name, platform, COUNT(*) as offline_count, 0 as avg_hours_offline')
                        ->groupBy('name', 'shop_name', 'platform')
                        ->orderBy('offline_count', 'desc')
                        ->limit(10)
                        ->get();
                }
                $categoryData = DB::table('items')
                    ->selectRaw("
                        category,
                        COUNT(DISTINCT (name || '|' || shop_name || '|' || platform)) as total_items,
                        ROUND(100.0 * SUM(CASE WHEN is_available = true THEN 1 ELSE 0 END) / COUNT(*), 1) as availability_percentage,
                        SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) as offline_count
                    ")
                    ->groupBy('category')
                    ->orderByRaw('CAST(category AS TEXT)')
                    ->get()
                    ->keyBy('category');
                return compact('itemStats', 'topOfflineItems', 'categoryData');
            });

            $this->line('  → store_comparison_db');
            Cache::remember('store_comparison_db', 300, function () {
                $shopIds = DB::table('platform_status')->select('shop_id')->distinct()->pluck('shop_id')->toArray();
                return [
                    'shopIds'             => $shopIds,
                    'allPlatformStatuses' => DB::table('platform_status')
                        ->whereIn('shop_id', $shopIds)
                        ->get()
                        ->groupBy('shop_id')
                        ->map(fn($items) => $items->keyBy('platform')),
                    'itemCounts'          => DB::table('items')
                        ->select(
                            'shop_name',
                            DB::raw('COUNT(*) as total'),
                            DB::raw('SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) as offline')
                        )
                        ->groupBy('shop_name')
                        ->get()
                        ->keyBy('shop_name'),
                ];
            });

            // ── Misc ──────────────────────────────────────────────────────────
            $this->line('  → last_sync_unix');
            Cache::remember('last_sync_unix', 60, function () {
                $candidates = array_filter([
                    DB::table('platform_status')->max('last_checked_at'),
                    DB::table('restosuite_item_snapshots')->max('updated_at'),
                ]);
                if (empty($candidates)) return 0;
                return \Carbon\Carbon::parse(max($candidates))->timestamp;
            });

            $elapsed = round((microtime(true) - $start) * 1000);
            $this->info("✅ Cache warm-up complete in {$elapsed}ms");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Cache warm-up failed: ' . $e->getMessage());
            // Don't fail the deploy — server can still warm caches on first request
            return Command::SUCCESS;
        }
    }
}
