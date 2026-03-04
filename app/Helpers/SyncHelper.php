<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncHelper
{
    /**
     * Get the last sync/update timestamp for consistent display across all pages.
     * Uses priority order: restosuite_item_snapshots > platform_status
     * Optional: can filter by shop_id for a specific store's timestamp.
     *
     * Static per-request cache + 60s cross-request Cache::remember to avoid
     * repeated Neon queries on the same or subsequent page loads.
     */
    public static function getLastSyncTimestamp(?int $shopId = null): string
    {
        // Static cache: avoids repeated DB calls within the same request
        static $cache = [];
        $key = $shopId ?? '__global__';
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        // Cross-request cache: 60s TTL saves 1 Neon query per page load
        $result = Cache::remember('last_sync_ts_' . $key, 60, function () use ($shopId) {
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

            return $lastSync
                ? Carbon::parse($lastSync)->setTimezone('Asia/Singapore')->format('M j, Y g:i A') . ' SGT'
                : 'Never';
        });

        $cache[$key] = $result;
        return $result;
    }
}
