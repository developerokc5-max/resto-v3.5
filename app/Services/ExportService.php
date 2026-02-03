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

        $data = [];
        foreach ($shopMap as $shopId => $shop) {
            $platformStatus = DB::table('platform_status')
                ->where('shop_id', $shopId)
                ->select('platform', 'is_online')
                ->get();

            $onlinePlatforms = $platformStatus->where('is_online', 1)->count();
            $totalPlatforms = $platformStatus->count();

            $items = DB::table('items')->where('shop_name', $shop['name'])->count();
            $offlineItems = DB::table('items')
                ->where('shop_name', $shop['name'])
                ->where('is_available', 0)
                ->count();

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

        $query = DB::table('store_status_logs');

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->get();

        $data = [];
        foreach ($shopMap as $shopId => $shop) {
            $shopLogs = $logs->where('shop_id', $shopId);
            $totalLogs = $shopLogs->count();
            $onlineLogs = $shopLogs->where('is_now_online', 1)->count();

            $uptime = $totalLogs > 0 ? round(($onlineLogs / $totalLogs) * 100, 2) : 0;

            $offlineItems = DB::table('items')
                ->where('shop_name', $shop['name'])
                ->where('is_available', 0)
                ->count();

            $totalItems = DB::table('items')
                ->where('shop_name', $shop['name'])
                ->count();

            $data[] = [
                'Store' => $shop['name'],
                'Brand' => $shop['brand'],
                '7-Day Uptime %' => $uptime,
                'Total Items' => $totalItems,
                'Offline Items' => $offlineItems,
                'Availability %' => $totalItems > 0 ? round(((($totalItems - $offlineItems) / $totalItems) * 100), 2) : 0,
                'Incidents (7d)' => $shopLogs->count(),
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
