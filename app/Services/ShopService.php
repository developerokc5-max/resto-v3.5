<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ShopService
{
    /**
     * Get all active shops with statistics
     * Used by ShopsIndex component
     *
     * @param string $searchQuery Optional search query
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\Paginator
     */
    public static function getAllShopsWithStats(string $searchQuery = '', int $perPage = 25)
    {
        $query = DB::table('restosuite_item_snapshots')
            ->select('shop_id', 'shop_name', 'brand_name')
            ->selectRaw('MAX(created_at) as last_seen')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as items_off');

        // Apply search filter if provided
        if ($searchQuery !== '') {
            $query->where(function ($sub) use ($searchQuery) {
                $sub->where('shop_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('brand_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('shop_id', 'like', '%' . $searchQuery . '%');
            });
        }

        return $query
            ->groupBy('shop_id', 'shop_name', 'brand_name')
            ->orderByDesc('last_seen')
            ->paginate($perPage);
    }

    /**
     * Get items for a specific shop
     * Used by ShopItems component
     *
     * @param string $shopId The shop ID
     * @param string $searchQuery Optional search query
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\Paginator
     */
    public static function getShopItems(string $shopId, string $searchQuery = '', int $perPage = 25)
    {
        $query = DB::table('restosuite_item_snapshots')
            ->select('id', 'name', 'item_id', 'price', 'is_active', 'shop_id', 'created_at', 'platform_name')
            ->where('shop_id', $shopId);

        // Apply search filter if provided
        if ($searchQuery !== '') {
            $query->where(function ($sub) use ($searchQuery) {
                $sub->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('item_id', 'like', '%' . $searchQuery . '%');
            });
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get count of offline items for a shop
     *
     * @param string $shopId The shop ID
     * @return int
     */
    public static function getOfflineItemsCount(string $shopId): int
    {
        return (int) DB::table('restosuite_item_snapshots')
            ->where('shop_id', $shopId)
            ->where('is_active', 0)
            ->count();
    }

    /**
     * Get offline items count with caching (5 minute cache)
     *
     * @param string $shopId The shop ID
     * @return int
     */
    public static function getOfflineItemsCountCached(string $shopId): int
    {
        $cacheKey = "shop.{$shopId}.offline_count";

        return Cache::remember($cacheKey, 300, function () use ($shopId) {
            return self::getOfflineItemsCount($shopId);
        });
    }

    /**
     * Get shop summary data
     *
     * @param string $shopId The shop ID
     * @return array
     */
    public static function getShopSummary(string $shopId): array
    {
        return [
            'shop_id' => $shopId,
            'total_items' => DB::table('restosuite_item_snapshots')
                ->where('shop_id', $shopId)
                ->count(),
            'offline_items' => self::getOfflineItemsCountCached($shopId),
            'online_items' => DB::table('restosuite_item_snapshots')
                ->where('shop_id', $shopId)
                ->where('is_active', 1)
                ->count(),
        ];
    }

    /**
     * Invalidate shop-related caches
     * Call this after data updates
     *
     * @param string $shopId Optional shop ID to clear specific shop cache
     */
    public static function invalidateCache(string $shopId = null)
    {
        if ($shopId) {
            Cache::forget("shop.{$shopId}.offline_count");
            CacheService::invalidateShopStatusCache($shopId);
        } else {
            Cache::flush(); // Flush all caches
        }
    }
}
