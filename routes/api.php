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

    // Trigger manual platform scraping - NEW VERSION
    Route::post('/scrape', function (Request $request) {
        // Increase timeout to 10 minutes for accurate scraping
        set_time_limit(600);

        try {
            // Use the NEW platform sync scraper (writes directly to database)
            $scriptPath = base_path('platform-test-trait-1/scrape_platform_sync.py');

            // Check if script exists
            if (!file_exists($scriptPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scraper script not found',
                    'path' => $scriptPath,
                ], 500);
            }

            // Run the Python scraper in background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - run Python script
                $logPath = storage_path('logs/platform_scraper.log');
                $command = "start /B python \"{$scriptPath}\" > \"{$logPath}\" 2>&1";
                pclose(popen($command, 'r'));

                return response()->json([
                    'success' => true,
                    'message' => 'Platform scraper started successfully',
                    'note' => 'Scraping all outlets across 3 platforms. This may take 5-10 minutes.',
                    'timestamp' => now()->toIso8601String(),
                    'log_file' => $logPath,
                ]);
            } else {
                // Linux/Mac - run in background
                $logPath = storage_path('logs/platform_scraper.log');
                $command = "nohup python3 \"{$scriptPath}\" > \"{$logPath}\" 2>&1 &";
                exec($command);

                return response()->json([
                    'success' => true,
                    'message' => 'Platform scraper started successfully',
                    'note' => 'Scraping all outlets across 3 platforms. This may take 5-10 minutes.',
                    'timestamp' => now()->toIso8601String(),
                    'log_file' => $logPath,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start platform scraper',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Trigger manual items scraping - BULLETPROOF VERSION
    Route::post('/scrape-items', function (Request $request) {
        // Increase timeout to 30 minutes (scraping all stores takes time)
        set_time_limit(1800);

        try {
            // Run the BULLETPROOF items scraper
            $scriptPath = base_path('_archive/scrapers/scrape_items_bulletproof.py');
            $command = "python \"{$scriptPath}\" 2>&1";

            exec($command, $output, $returnCode);
            $rawOutput = implode("\n", $output);

            if ($returnCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Items scraper execution failed',
                    'error' => $rawOutput,
                ], 500);
            }

            // Parse JSON output - extract JSON from potentially mixed output
            $jsonStart = strpos($rawOutput, '{');
            if ($jsonStart !== false) {
                $jsonOutput = substr($rawOutput, $jsonStart);
                $data = json_decode($jsonOutput, true);
            } else {
                $data = null;
            }

            if (!$data || !isset($data['success']) || !$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse items scraper output',
                    'output' => $rawOutput,
                ], 500);
            }

            // Save to cache
            $cacheFile = storage_path('app/items_data_cache.json');
            file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));

            // Clear existing items
            DB::table('items')->truncate();

            // Import all items into database
            $totalImported = 0;
            foreach ($data['stores'] as $storeName => $items) {
                foreach ($items as $item) {
                    // Insert for each platform (since they may have different availability)
                    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                        DB::table('items')->insert([
                            'item_id' => $item['sku'] ?: 'unknown',
                            'shop_name' => $storeName,
                            'name' => $item['name'],
                            'sku' => $item['sku'],
                            'category' => $item['category'],
                            'price' => $item['price'],
                            'image_url' => $item['image_url'],
                            'is_available' => $item['is_available'],
                            'platform' => $platform,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalImported++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Items scraping completed successfully',
                'stats' => [
                    'total_stores' => count($data['stores']),
                    'total_items' => $data['total_items'],
                    'items_imported' => $totalImported,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Items scraping failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Trigger RestoSuite API sync
    Route::post('/resosuite', function (Request $request) {
        try {
            \Artisan::call('resosuite:sync-items');
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'RestoSuite sync completed successfully',
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'RestoSuite sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Trigger Items Scraper - NEW BULLETPROOF VERSION
    Route::post('/items/sync', function (Request $request) {
        set_time_limit(1800); // 30 minutes timeout

        try {
            // Use the NEW bulletproof scraper
            $scriptPath = base_path('scrape_items_bulletproof.py');

            // Run the Python scraper in background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - run in background
                $command = "start /B python \"{$scriptPath}\" > " . storage_path('logs/scraper.log') . " 2>&1";
                pclose(popen($command, 'r'));

                return response()->json([
                    'success' => true,
                    'message' => 'Items scraper started in background',
                    'note' => 'Scraping ~35 stores across 3 platforms. Check back in 10-15 minutes.',
                    'timestamp' => now()->toIso8601String(),
                ]);
            } else {
                // Linux/Mac - run in background
                $command = "nohup python3 \"{$scriptPath}\" > " . storage_path('logs/scraper.log') . " 2>&1 &";
                exec($command);

                return response()->json([
                    'success' => true,
                    'message' => 'Items scraper started in background',
                    'note' => 'Scraping ~35 stores across 3 platforms. Check back in 10-15 minutes.',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start scraper',
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

// Items Management API
Route::prefix('v1/items')->group(function () {

    // Trigger Items Sync - Uses NEW scrape_items_sync.py
    Route::post('/sync', function (Request $request) {
        // Increase timeout to 30 minutes (scraping all stores takes time)
        set_time_limit(1800);

        try {
            // Use the NEW items sync scraper (isolated from other pages)
            $scriptPath = base_path('item-test-trait-1/scrape_items_sync.py');

            // Check if script exists
            if (!file_exists($scriptPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scraper script not found',
                    'path' => $scriptPath,
                ], 500);
            }

            // Run the Python scraper in background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - run Python script
                $logPath = storage_path('logs/items_scraper.log');
                $command = "start /B python \"{$scriptPath}\" > \"{$logPath}\" 2>&1";
                pclose(popen($command, 'r'));

                return response()->json([
                    'success' => true,
                    'message' => 'Items scraper started successfully',
                    'note' => 'Scraping all outlets across 3 platforms. This may take 10-15 minutes.',
                    'timestamp' => now()->toIso8601String(),
                    'log_file' => $logPath,
                ]);
            } else {
                // Linux/Mac - run in background
                $logPath = storage_path('logs/items_scraper.log');
                $command = "nohup python3 \"{$scriptPath}\" > \"{$logPath}\" 2>&1 &";
                exec($command);

                return response()->json([
                    'success' => true,
                    'message' => 'Items scraper started successfully',
                    'note' => 'Scraping all outlets across 3 platforms. This may take 10-15 minutes.',
                    'timestamp' => now()->toIso8601String(),
                    'log_file' => $logPath,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start items scraper',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Toggle item availability status
    Route::post('/toggle-status', function (Request $request) {
        try {
            $validated = $request->validate([
                'item_id' => 'required|integer',
                'is_available' => 'required|boolean',
                'platform' => 'required|string|in:grab,foodpanda,deliveroo',
            ]);

            $updated = DB::table('items')
                ->where('id', $validated['item_id'])
                ->where('platform', $validated['platform'])
                ->update([
                    'is_available' => $validated['is_available'],
                    'updated_at' => now(),
                ]);

            if ($updated) {
                // Get updated item info
                $item = DB::table('items')->where('id', $validated['item_id'])->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Item status updated successfully',
                    'data' => [
                        'item_id' => $validated['item_id'],
                        'platform' => $validated['platform'],
                        'is_available' => $validated['is_available'],
                        'item_name' => $item->name ?? 'Unknown',
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found or no changes made',
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item status',
                'error' => $e->getMessage(),
            ], 500);
        }
    });

    // Get all items with platform status
    Route::get('/list', function () {
        $items = DB::table('items')
            ->select('id', 'item_id', 'shop_name', 'name', 'sku', 'category', 'price', 'is_available', 'platform')
            ->orderBy('shop_name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count(),
        ]);
    });

    // Get items for specific shop
    Route::get('/shop/{shopName}', function ($shopName) {
        $items = DB::table('items')
            ->where('shop_name', $shopName)
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No items found for this shop',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count(),
        ]);
    });

    // Bulk update item status
    Route::post('/bulk-toggle', function (Request $request) {
        try {
            $validated = $request->validate([
                'item_ids' => 'required|array',
                'item_ids.*' => 'integer',
                'is_available' => 'required|boolean',
            ]);

            $updated = DB::table('items')
                ->whereIn('id', $validated['item_ids'])
                ->update([
                    'is_available' => $validated['is_available'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} items successfully",
                'count' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update items',
                'error' => $e->getMessage(),
            ], 500);
        }
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
    ]);
});
