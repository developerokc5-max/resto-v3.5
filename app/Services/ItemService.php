<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ItemService
{
    /**
     * Get all items with optional filtering
     *
     * @param array $filters Optional filters (shop_id, category, status, etc.)
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\Paginator
     */
    public static function getItems(array $filters = [], int $perPage = 25)
    {
        $query = DB::table('restosuite_item_snapshots')
            ->select('id', 'name', 'item_id', 'price', 'is_active', 'shop_id', 'category', 'platform_name', 'created_at');

        // Apply filters
        if (isset($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status']);
        }

        if (isset($filters['platform'])) {
            $query->where('platform_name', $filters['platform']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('item_id', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Get item statistics
     *
     * @return array
     */
    public static function getItemStats(): array
    {
        $cacheKey = 'items.stats';

        return Cache::remember($cacheKey, 3600, function () {
            return [
                'total_items' => DB::table('restosuite_item_snapshots')->count(),
                'online_items' => DB::table('restosuite_item_snapshots')
                    ->where('is_active', 1)
                    ->count(),
                'offline_items' => DB::table('restosuite_item_snapshots')
                    ->where('is_active', 0)
                    ->count(),
                'total_shops' => DB::table('restosuite_item_snapshots')
                    ->distinct('shop_id')
                    ->count(),
            ];
        });
    }

    /**
     * Get items grouped by category
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getItemsByCategory()
    {
        $cacheKey = 'items.by_category';

        return Cache::remember($cacheKey, 86400, function () {
            return DB::table('restosuite_item_snapshots')
                ->select('category')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('count')
                ->get();
        });
    }

    /**
     * Get items grouped by platform
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getItemsByPlatform()
    {
        $cacheKey = 'items.by_platform';

        return Cache::remember($cacheKey, 3600, function () {
            return DB::table('restosuite_item_snapshots')
                ->select('platform_name')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as online')
                ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as offline')
                ->groupBy('platform_name')
                ->orderByDesc('count')
                ->get();
        });
    }

    /**
     * Get offline items by shop
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getOfflineItemsByShop()
    {
        $cacheKey = 'items.offline_by_shop';

        return Cache::remember($cacheKey, 1800, function () {
            return DB::table('restosuite_item_snapshots')
                ->where('is_active', 0)
                ->select('shop_id', 'shop_name')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('shop_id', 'shop_name')
                ->orderByDesc('count')
                ->get();
        });
    }

    /**
     * Invalidate item-related caches
     */
    public static function invalidateCache()
    {
        Cache::forget('items.stats');
        Cache::forget('items.by_category');
        Cache::forget('items.by_platform');
        Cache::forget('items.offline_by_shop');
    }
}
