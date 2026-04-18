<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cache Optimization Helper
 *
 * Consolidates multiple cache calls into single operations
 * and implements optimized TTL values based on data change frequency
 */
class CacheOptimizationHelper
{
    // Cache TTL values (in seconds) - optimized for data change frequency
    // Scrapers explicitly call invalidateDashboardCaches() on each run, so TTLs are
    // just a safety net — longer values reduce unnecessary DB round-trips between runs
    const CACHE_TTL_FAST = 300;          // 5 minutes - platform status (scraper invalidates anyway)
    const CACHE_TTL_MODERATE = 600;      // 10 minutes - items, alerts, dashboard KPIs
    const CACHE_TTL_SLOW = 3600;         // 1 hour - store stats, reports
    const CACHE_TTL_VERY_SLOW = 86400;   // 24 hours - shop names, brands

    /**
     * Get consolidated dashboard KPIs
     *
     * Consolidates 6+ separate cache calls into 1
     * Returns all dashboard metrics in a single cached array
     */
    public static function getDashboardKPIs()
    {
        return Cache::remember('dashboard_kpis_consolidated', self::CACHE_TTL_MODERATE, function () {
            $shopMap       = ShopHelper::getShopMap();
            $excludedIds   = ShopHelper::excludedShopIds();
            $excludedNames = ShopHelper::excludedShopNames();

            // Total distinct stores (excluding test/demo/closed).
            // platform_status is the source of truth — one row per real shop_id per platform.
            // restosuite_item_snapshots may contain orphan/legacy shop_ids that inflate the count.
            // NOTE: use ->distinct('shop_id')->count('shop_id') — plain ->distinct()->count()
            // resolves to COUNT(*) in Laravel's builder, which would return the raw row count
            // (92 = 46 shops × 2 platforms) instead of the distinct shop count (46).
            $storeCount = DB::table('platform_status')
                ->whereNotIn('shop_id', $excludedIds)
                ->distinct('shop_id')
                ->count('shop_id');

            if ($storeCount === 0) {
                // Fallback only if platform_status is empty (scraper hasn't run yet)
                $storeCount = DB::table('restosuite_item_snapshots')
                    ->whereNotIn('shop_id', $excludedIds)
                    ->distinct('shop_id')
                    ->count('shop_id');
            }

            // Single aggregated query for items and status.
            // Count DISTINCT on normalized (shop_name + name) so "(Del)"/"(Onl)" variants and
            // per-platform duplicates don't inflate the dashboard totals.
            $normNameSql = "TRIM(TRAILING '.' FROM TRIM(REGEXP_REPLACE(name, '\\s*\\(\\s*(Del|Onl)\\s*\\)\\s*', '', 'gi')))";
            $itemsStatus = DB::table('items')
                ->select(
                    DB::raw("COUNT(DISTINCT CASE WHEN is_available = false THEN shop_name || '|' || {$normNameSql} END) as items_off"),
                    DB::raw("COUNT(DISTINCT shop_name || '|' || {$normNameSql}) as total_items")
                )
                ->whereIn('platform', ['grab', 'foodpanda'])
                ->whereNotIn('shop_name', $excludedNames)
                ->first();

            // Count distinct stores that have at least one platform offline
            $alertCount = DB::table('platform_status')
                ->where('is_online', false)
                ->whereNotIn('shop_id', $excludedIds)
                ->distinct('shop_id')
                ->count('shop_id');

            // Single aggregated query for platform stats
            $platformStats = DB::table('platform_status')
                ->select(
                    DB::raw('COUNT(CASE WHEN is_online = true THEN 1 END) as platforms_online'),
                    DB::raw('COUNT(*) as platforms_total')
                )
                ->whereNotIn('shop_id', $excludedIds)
                ->first();

            $storesOnline = max(0, $storeCount - $alertCount);

            return [
                'stores_online' => $storesOnline,
                'items_off' => (int) ($itemsStatus?->items_off ?? 0),
                'addons_off' => 0,
                'alerts' => (int) $alertCount,
                'platforms_online' => (int) ($platformStats?->platforms_online ?? 0),
                'platforms_total' => (int) ($platformStats?->platforms_total ?? 0),
                'platforms_offline' => (int) (($platformStats?->platforms_total ?? 0) - ($platformStats?->platforms_online ?? 0)),
                'shops_affected' => (int) $alertCount,
            ];
        });
    }

    /**
     * Get consolidated alert data
     *
     * Consolidates multiple queries into single operation
     * Returns all alert metrics at once
     */
    public static function getAlertMetrics()
    {
        return Cache::remember('alert_metrics_consolidated', self::CACHE_TTL_MODERATE, function () {
            $excludedIds   = ShopHelper::excludedShopIds();
            $excludedNames = ShopHelper::excludedShopNames();

            // Single query to get all alert data (excluding test/demo/closed stores)
            $offlineStores = DB::table('platform_status')
                ->select('shop_id', DB::raw('COUNT(*) as offline_count'))
                ->where('is_online', false)
                ->whereNotIn('shop_id', $excludedIds)
                ->groupBy('shop_id')
                ->get();

            $fullyOfflineCount = $offlineStores->filter(function ($store) {
                return $store->offline_count === 2;
            })->count();

            $partiallyOfflineCount = $offlineStores->filter(function ($store) {
                return $store->offline_count < 2;
            })->count();

            // Get offline items count (distinct normalized items, not raw rows).
            $normNameSql = "TRIM(TRAILING '.' FROM TRIM(REGEXP_REPLACE(name, '\\s*\\(\\s*(Del|Onl)\\s*\\)\\s*', '', 'gi')))";
            $offlineItems = (int) DB::table('items')
                ->where('is_available', false)
                ->whereIn('platform', ['grab', 'foodpanda'])
                ->whereNotIn('shop_name', $excludedNames)
                ->selectRaw("COUNT(DISTINCT shop_name || '|' || {$normNameSql}) as c")
                ->value('c');

            return [
                'fully_offline_stores' => $fullyOfflineCount,
                'partially_offline_stores' => $partiallyOfflineCount,
                'offline_items_count' => $offlineItems,
                'total_alerts' => $fullyOfflineCount + $partiallyOfflineCount,
            ];
        });
    }

    /**
     * Get store stats with consolidated cache
     *
     * Retrieves all store stats in single cached operation
     * Much faster than querying each store individually
     */
    public static function getConsolidatedStoreStats()
    {
        return Cache::remember('store_stats_consolidated', self::CACHE_TTL_MODERATE, function () {
            // Single query to get all stats grouped by shop (excluding test/demo/closed stores)
            return DB::table('restosuite_item_snapshots as s')
                ->select(
                    's.shop_id',
                    DB::raw('COUNT(*) as total_items'),
                    DB::raw('SUM(CASE WHEN s.is_active = false THEN 1 ELSE 0 END) as items_off'),
                    DB::raw('MAX(s.updated_at) as last_sync')
                )
                ->whereNotIn('s.shop_id', ShopHelper::excludedShopIds())
                ->groupBy('s.shop_id')
                ->get()
                ->keyBy('shop_id');
        });
    }

    /**
     * Get offline items count per shop per platform
     *
     * Single query consolidation - much faster than multiple queries
     */
    public static function getOfflineItemsPerShopPlatform()
    {
        return Cache::remember('offline_items_by_shop_platform', self::CACHE_TTL_MODERATE, function () {
            return DB::table('items')
                ->select('shop_name', 'platform', DB::raw('COUNT(*) as offline_count'))
                ->where('is_available', false)
                ->whereNotIn('shop_name', ShopHelper::excludedShopNames())
                ->groupBy('shop_name', 'platform')
                ->get()
                ->keyBy(function ($item) {
                    return $item->shop_name . '|' . $item->platform;
                });
        });
    }

    /**
     * Get all platform statuses consolidated
     *
     * Single query replaces N+1 pattern
     */
    public static function getAllPlatformStatuses()
    {
        return Cache::remember('all_platform_statuses', self::CACHE_TTL_FAST, function () {
            return DB::table('platform_status')
                ->whereNotIn('shop_id', ShopHelper::excludedShopIds())
                ->get()
                ->groupBy('shop_id');
        });
    }

    /**
     * Get recent changes count per shop
     *
     * Single query consolidation
     */
    public static function getRecentChangesPerShop($days = 1)
    {
        $cacheKey = "recent_changes_per_shop_" . $days . "d";

        return Cache::remember($cacheKey, self::CACHE_TTL_MODERATE, function () use ($days) {
            return DB::table('restosuite_item_changes')
                ->select('shop_id', DB::raw('COUNT(*) as change_count'))
                ->whereDate('created_at', '>=', now()->subDays($days))
                ->groupBy('shop_id')
                ->pluck('change_count', 'shop_id');
        });
    }

    /**
     * Invalidate dashboard-related caches
     *
     * Call this after scraper runs to refresh dashboard data
     */
    public static function invalidateDashboardCaches()
    {
        // Dashboard
        Cache::forget('dashboard_kpis_consolidated');
        Cache::forget('alert_metrics_consolidated');
        Cache::forget('store_stats_consolidated');
        Cache::forget('dashboard_downtime_30d');
        Cache::forget('dashboard_open_alerts');
        Cache::forget('dashboard_maintenance_shops');

        // Platform / store pages
        Cache::forget('offline_items_by_shop_platform');
        Cache::forget('all_platform_statuses');
        Cache::forget('stores_page_data');
        Cache::forget('platforms_page_data');
        Cache::forget('offline_items_page');
        Cache::forget('shop_map');

        // Alerts
        Cache::forget('alerts_db_data');

        // Reports
        Cache::forget('reports_daily_trends');
        Cache::forget('reports_platform_reliability');
        Cache::forget('reports_item_performance_v2');
        Cache::forget('reports_downtime_leaderboard');
        Cache::forget('store_comparison_db');

        // Scraper status
        Cache::forget('scraper_status_db');
        Cache::forget('scraper_logs_recent');

        // Change-tracking helpers
        Cache::forget('recent_changes_per_shop_1d');
        Cache::forget('recent_changes_per_shop_7d');

        // Per-store detail caches (keyed by shop_id — must loop)
        DB::table('shops')->pluck('shop_id')->each(function ($shopId) {
            Cache::forget('store_detail_data_' . $shopId);
        });
    }

    /**
     * Invalidate all caches
     *
     * Use after major data updates or during maintenance
     */
    public static function invalidateAllCaches()
    {
        Cache::flush();
    }

    /**
     * Get cache statistics
     *
     * Returns info about current cache performance
     */
    public static function getCacheStats()
    {
        return [
            'ttl_fast' => self::CACHE_TTL_FAST . 's (5 min)',
            'ttl_moderate' => self::CACHE_TTL_MODERATE . 's (10 min)',
            'ttl_slow' => self::CACHE_TTL_SLOW . 's (1 hour)',
            'ttl_very_slow' => self::CACHE_TTL_VERY_SLOW . 's (24 hours)',
            'cache_store' => config('cache.default'),
            'recommendation' => config('cache.default') === 'file'
                ? 'Consider upgrading to Redis for 10-100x faster cache operations'
                : 'Cache store is optimized',
        ];
    }
}
