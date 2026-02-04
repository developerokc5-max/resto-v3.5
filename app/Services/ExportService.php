<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExportService
{
    /**
     * Export Overview Report (all stores & platforms)
     */
    public static function exportOverviewReport()
    {
        $shopMap = app('ShopHelper')->getShopMap();
        $shopIds = array_keys($shopMap);

        // Single query for all platform statuses
        $platformStatusMap = DB::table('platform_status')
            ->whereIn('shop_id', $shopIds)
            ->select('shop_id', 'platform', 'is_online')
            ->get()
            ->groupBy('shop_id');

        // Single query for all items with aggregation
        $itemStats = DB::table('items')
            ->whereIn('shop_name', array_values(array_column($shopMap, 'name')))
            ->select(
                'shop_name',
                DB::raw('COUNT(*) as total_items'),
                DB::raw('SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_items')
            )
            ->groupBy('shop_name')
            ->get()
            ->keyBy('shop_name');

        $data = [];
        foreach ($shopMap as $shopId => $shop) {
            $platformStatuses = $platformStatusMap->get($shopId, collect());
            $onlinePlatforms = $platformStatuses->where('is_online', 1)->count();
            $totalPlatforms = $platformStatuses->count();

            $stats = $itemStats->get($shop['name'], (object)['total_items' => 0, 'offline_items' => 0]);
            $items = $stats->total_items ?? 0;
            $offlineItems = $stats->offline_items ?? 0;

            $availability = $items > 0 ? round(((($items - $offlineItems) / $items) * 100), 2) : 0;

            $data[] = [
                'Store Name' => $shop['name'],
                'Brand' => $shop['brand'],
                'Platforms Online' => $onlinePlatforms . '/' . $totalPlatforms,
                'Total Items' => $items,
                'Offline Items' => $offlineItems,
                'Availability %' => $availability,
                'Status' => $onlinePlatforms == $totalPlatforms ? 'All Online' : ($onlinePlatforms > 0 ? 'Mixed' : 'All Offline'),
            ];
        }

        return $data;
    }

    /**
     * Export All Items
     */
    public static function exportAllItems($dataType = 'all', $platforms = [], $dateFrom = null, $dateTo = null, $includeImages = false)
    {
        $query = DB::table('items')->select(
            'shop_name',
            'item_name',
            'platform',
            'is_available',
            'item_image',
            'price',
            'created_at'
        );

        // Filter by data type
        if ($dataType === 'offline') {
            $query->where('is_available', 0);
        }

        // Filter by platforms
        if (!empty($platforms)) {
            $query->whereIn('platform', $platforms);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $items = $query->orderBy('shop_name')->orderBy('item_name')->get();

        $data = [];
        foreach ($items as $item) {
            $row = [
                'Store' => $item->shop_name,
                'Item Name' => $item->item_name,
                'Platform' => $item->platform,
                'Status' => $item->is_available ? 'Available' : 'Offline',
                'Price' => $item->price ?? 'N/A',
                'Updated' => Carbon::parse($item->created_at)->format('M d, Y H:i'),
            ];

            if ($includeImages && $item->item_image) {
                $row['Image URL'] = $item->item_image;
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Export Platform Status
     */
    public static function exportPlatformStatus($platforms = [], $dateFrom = null, $dateTo = null)
    {
        $shopMap = app('ShopHelper')->getShopMap();

        $query = DB::table('platform_status')->select(
            'shop_id',
            'platform',
            'is_online',
            'updated_at'
        );

        if (!empty($platforms)) {
            $query->whereIn('platform', $platforms);
        }

        $statusData = $query->get();

        $data = [];
        foreach ($statusData as $status) {
            $shop = $shopMap[$status->shop_id] ?? ['name' => 'Unknown'];
            $data[] = [
                'Store' => $shop['name'],
                'Platform' => $status->platform,
                'Status' => $status->is_online ? 'Online' : 'Offline',
                'Last Updated' => Carbon::parse($status->updated_at)->format('M d, Y H:i'),
            ];
        }

        return $data;
    }

    /**
     * Export Store Logs (Historical Data)
     */
    public static function exportStoreLogs($dateFrom = null, $dateTo = null)
    {
        $shopMap = app('ShopHelper')->getShopMap();

        $query = DB::table('store_status_logs')->select(
            'shop_id',
            'platform',
            'was_online',
            'is_now_online',
            'created_at'
        );

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $data = [];
        foreach ($logs as $log) {
            $shop = $shopMap[$log->shop_id] ?? ['name' => 'Unknown'];
            $data[] = [
                'Store' => $shop['name'],
                'Platform' => $log->platform,
                'Previous Status' => $log->was_online ? 'Online' : 'Offline',
                'Current Status' => $log->is_now_online ? 'Online' : 'Offline',
                'Event Time' => Carbon::parse($log->created_at)->format('M d, Y H:i'),
                'Type' => $log->was_online && !$log->is_now_online ? 'Went Offline' : 'Came Online',
            ];
        }

        return $data;
    }

    /**
     * Export Analytics Report
     */
    public static function exportAnalyticsReport($dateFrom = null, $dateTo = null)
    {
        $shopMap = app('ShopHelper')->getShopMap();
        $shopIds = array_keys($shopMap);
        $shopNames = array_values(array_column($shopMap, 'name'));

        // Single query for all logs with aggregation
        $logsQuery = DB::table('store_status_logs');

        if ($dateFrom) {
            $logsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $logsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $logsStats = $logsQuery
            ->whereIn('shop_id', $shopIds)
            ->select(
                'shop_id',
                DB::raw('COUNT(*) as total_logs'),
                DB::raw('SUM(CASE WHEN is_now_online = 1 THEN 1 ELSE 0 END) as online_logs')
            )
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');

        // Single query for all items with aggregation
        $itemStats = DB::table('items')
            ->whereIn('shop_name', $shopNames)
            ->select(
                'shop_name',
                DB::raw('COUNT(*) as total_items'),
                DB::raw('SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as offline_items')
            )
            ->groupBy('shop_name')
            ->get()
            ->keyBy('shop_name');

        $data = [];
        foreach ($shopMap as $shopId => $shop) {
            $logStats = $logsStats->get($shopId, (object)['total_logs' => 0, 'online_logs' => 0]);
            $totalLogs = $logStats->total_logs ?? 0;
            $onlineLogs = $logStats->online_logs ?? 0;
            $uptime = $totalLogs > 0 ? round(($onlineLogs / $totalLogs) * 100, 2) : 0;

            $stats = $itemStats->get($shop['name'], (object)['total_items' => 0, 'offline_items' => 0]);
            $totalItems = $stats->total_items ?? 0;
            $offlineItems = $stats->offline_items ?? 0;

            $data[] = [
                'Store' => $shop['name'],
                'Brand' => $shop['brand'],
                '7-Day Uptime %' => $uptime,
                'Total Items' => $totalItems,
                'Offline Items' => $offlineItems,
                'Availability %' => $totalItems > 0 ? round(((($totalItems - $offlineItems) / $totalItems) * 100), 2) : 0,
                'Incidents (7d)' => $totalLogs,
            ];
        }

        return $data;
    }

    /**
     * Convert array to CSV content
     */
    public static function arrayToCSV($data)
    {
        if (empty($data)) {
            return '';
        }

        $output = '';

        // Headers
        $headers = array_keys($data[0]);
        $output .= implode(',', array_map(function($h) {
            return '"' . str_replace('"', '""', $h) . '"';
        }, $headers)) . "\n";

        // Data rows
        foreach ($data as $row) {
            $output .= implode(',', array_map(function($val) {
                return '"' . str_replace('"', '""', $val ?? '') . '"';
            }, $row)) . "\n";
        }

        return $output;
    }
}
