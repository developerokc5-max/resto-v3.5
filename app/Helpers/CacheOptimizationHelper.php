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
    const CACHE_TTL_FAST = 60;           // 1 minute - for frequently changing data (platform status)
    const CACHE_TTL_MODERATE = 300;      // 5 minutes - for moderately changing data (items, alerts)
    const CACHE_TTL_SLOW = 3600;         // 1 hour - for slowly changing data (store stats, reports)
    const CACHE_TTL_VERY_SLOW = 86400;   // 24 hours - for rarely changing data (shop names, brands)

    /**
     * Get consolidated dashboard KPIs
     *
     * Consolidates 6+ separate cache calls into 1
     * Returns all dashboard metrics in a single cached array
     */
    public static function getDashboardKPIs()
    {
        return Cache::remember('dashboard_kpis_consolidated', self::CACHE_TTL_MODERATE, function () {
            $shopMap = ShopHelper::getShopMap();

            // Single query for store count (using DISTINCT with GROUP BY)
            $storeCount = DB::table('restosuite_item_snapshots')
                ->select('shop_id')
                ->distinct()
                ->count();

            if ($storeCount == 0) {
                $storeCount = DB::table('platform_status')
                    ->select('shop_id')
                    ->distinct()
                    ->count();
            }

            // Single aggregated query for items and status
            $itemsStatus = DB::table('items')
                ->select(
                    DB::raw('COUNT(CASE WHEN is_available = 0 THEN 1 END) as items_off'),
                    DB::raw('COUNT(*) as total_items')
                )
                ->first();

            // Single aggregated query for changes today
            $changesStatus = DB::table('restosuite_item_changes')
                ->whereDate('created_at', today())
                ->select(
                    DB::raw('COUNT(*) as change_count'),
                    DB::raw('COUNT(DISTINCT shop_id) as shops_affected')
                )
                ->first();

            // Fallback if no changes
            if ($changesStatus->change_count == 0) {
                $changesStatus = DB::table('platform_status')
                    ->where('is_online', 0)
                    ->select(
                        DB::raw('COUNT(DISTINCT shop_id) as change_count'),
                        DB::raw('COUNT(DISTINCT shop_id) as shops_affected')
                    )
                    ->first();
            }

            // Single aggregated query for platform stats
            $platformStats = DB::table('platform_status')
                ->select(
                    DB::raw('COUNT(CASE WHEN is_online = 1 THEN 1 END) as platforms_online'),
                    DB::raw('COUNT(*) as platforms_total')
                )
                ->first();

            return [
                'stores_online' => (int) $storeCount,
                'items_off' => (int) ($itemsStatus?->items_off ?? 0),
                'addons_off' => 0,
                'alerts' => (int) ($changesStatus?->change_count ?? 0),
                'platforms_online' => (int) ($platformStats?->platforms_online ?? 0),
                'platforms_total' => (int) ($platformStats?->platforms_total ?? 0),
                'platforms_offline' => (int) (($platformStats?->platforms_total ?? 0) - ($platformStats?->platforms_online ?? 0)),
                'shops_affected' => (int) ($changesStatus?->shops_affected ?? 0),
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
            // Single query to get all alert data
            $offlineStores = DB::table('platform_status')
                ->select('shop_id', DB::raw('COUNT(*) as offline_count'))
                ->where('is_online', 0)
                ->groupBy('shop_id')
                ->get();

            $fullyOfflineCount = $offlineStores->filter(function ($store) {
                return $store->offline_count === 3;
            })->count();

            $partiallyOfflineCount = $offlineStores->filter(function ($store) {
                return $store->offline_count < 3;
            })->count();

            // Get offline items count in single query
            $offlineItems = DB::table('items')
                ->where('is_available', 0)
                ->count();

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
            // Single query to get all stats grouped by shop
            return DB::table('restosuite_item_snapshots as s')
                ->select(
                    's.shop_id',
                    DB::raw('COUNT(*) as total_items'),
                    DB::raw('SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) as items_off'),
                    DB::raw('MAX(s.updated_at) as last_sync')
                )
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
                ->where('is_available', 0)
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
        Cache::forget('dashboard_kpis_consolidated');
        Cache::forget('alert_metrics_consolidated');
        Cache::forget('store_stats_consolidated');
        Cache::forget('offline_items_by_shop_platform');
        Cache::forget('all_platform_statuses');
        Cache::forget('recent_changes_per_shop_1d');
        Cache::forget('recent_changes_per_shop_7d');
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
            'ttl_fast' => self::CACHE_TTL_FAST . 's (1 min)',
            'ttl_moderate' => self::CACHE_TTL_MODERATE . 's (5 min)',
            'ttl_slow' => self::CACHE_TTL_SLOW . 's (1 hour)',
            'ttl_very_slow' => self::CACHE_TTL_VERY_SLOW . 's (24 hours)',
            'cache_store' => config('cache.default'),
            'recommendation' => config('cache.default') === 'file'
                ? 'Consider upgrading to Redis for 10-100x faster cache operations'
                : 'Cache store is optimized',
        ];
    }
}
