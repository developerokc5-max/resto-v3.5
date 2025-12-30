<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PlatformStatus;
use Illuminate\Support\Facades\DB;
use App\Helpers\ShopHelper;

/**
 * API Routes for Hybrid System
 */

// Platform Status API
Route::prefix('platform')->group(function () {

    // Get all platform statuses
    Route::get('/status', function () {
        $statuses = PlatformStatus::with([])
            ->orderBy('last_checked_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $statuses,
            'meta' => [
                'total' => $statuses->count(),
                'online' => $statuses->where('is_online', true)->count(),
                'offline' => $statuses->where('is_online', false)->count(),
            ],
        ]);
    });

    // Get status for specific shop
    Route::get('/status/{shopId}', function (string $shopId) {
        $statuses = PlatformStatus::where('shop_id', $shopId)->get();

        if ($statuses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No platform status found for this shop',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shop_id' => $shopId,
                'platforms' => $statuses->keyBy('platform'),
            ],
        ]);
    });

    // Get statistics by platform
    Route::get('/stats', function () {
        $stats = PlatformStatus::getStatsByPlatform();

        return response()->json([
            'success' => true,
            'data' => $stats,
            'overall' => [
                'online_percentage' => PlatformStatus::getOnlinePercentage(),
                'total_connections' => PlatformStatus::count(),
            ],
        ]);
    });

    // Get online platforms
    Route::get('/online', function () {
        $online = PlatformStatus::online()
            ->recentlyChecked()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $online,
            'count' => $online->count(),
        ]);
    });

    // Get offline platforms
    Route::get('/offline', function () {
        $offline = PlatformStatus::offline()
            ->recentlyChecked()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offline,
            'count' => $offline->count(),
        ]);
    });

    // Get stale data (not checked recently)
    Route::get('/stale', function () {
        $stale = PlatformStatus::stale(30)->get();

        return response()->json([
            'success' => true,
            'data' => $stale,
            'count' => $stale->count(),
            'message' => 'Platform statuses not checked in last 30 minutes',
        ]);
    });
});

// Sync API
Route::prefix('sync')->group(function () {

    // Trigger manual scraping
    Route::post('/scrape', function (Request $request) {
        $limit = $request->input('limit', 10);
        $platform = $request->input('platform');

        try {
            $command = "scrape:platform-status --limit={$limit}";
            if ($platform) {
                $command .= " --platform={$platform}";
            }

            \Artisan::call($command);
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Scraping completed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scraping failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Clear cache
    Route::post('/clear-cache', function () {
        \Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    });
});

// Health check
Route::get('/health', function () {
    $lastSync = DB::table('platform_status')->max('last_checked_at');
    $totalShops = DB::table('platform_status')->distinct('shop_id')->count('shop_id');
    $onlinePlatforms = DB::table('platform_status')->where('is_online', 1)->count();
    $totalPlatforms = DB::table('platform_status')->count();

    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'hybrid_system' => [
            'last_scrape' => $lastSync,
            'shops_monitored' => $totalShops,
            'platforms_online' => $onlinePlatforms,
            'platforms_total' => $totalPlatforms,
            'online_percentage' => $totalPlatforms > 0 ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
        ],
        'api_sync' => [
            'last_sync' => DB::table('restosuite_item_snapshots')->max('updated_at'),
            'total_items' => DB::table('restosuite_item_snapshots')->count(),
        ],
    ]);
});
