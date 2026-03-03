<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PlatformStatus;
use Illuminate\Support\Facades\DB;
use App\Helpers\ShopHelper;
use App\Http\Controllers\Api\MonitorController;

/**
 * API Routes for Hybrid System
 */

// ========================================
// PROTECTED MOBILE APP MONITORING API
// ========================================
Route::middleware('auth:sanctum')->prefix('monitor')->group(function () {
    Route::get('/dashboard', [MonitorController::class, 'dashboard']);
    Route::get('/scraper/status', [MonitorController::class, 'scraperStatus']);
    Route::get('/shops', [MonitorController::class, 'shops']);
    Route::get('/items', [MonitorController::class, 'items']);
    Route::get('/changes', [MonitorController::class, 'recentChanges']);
    Route::get('/platform-status', [MonitorController::class, 'platformStatus']);
    Route::get('/statistics', [MonitorController::class, 'statistics']);
    Route::get('/webapp-health', [MonitorController::class, 'webappHealth']);
});

// Test endpoint to verify auth is working
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ========================================
// LEGACY/PUBLIC API (UNPROTECTED)
// ========================================

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

    // Trigger manual platform scraping via GitHub Actions workflow_dispatch
    Route::post('/scrape', function (Request $request) {
        $pat = env('GITHUB_PAT');

        if (!$pat) {
            return response()->json([
                'success' => false,
                'message' => 'GITHUB_PAT not configured.',
            ], 500);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $pat,
            'Accept'        => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->post('https://api.github.com/repos/developerokc5-max/resto-v3.5/actions/workflows/scrape-platform.yml/dispatches', [
            'ref' => 'main',
        ]);

        if ($response->status() === 204) {
            return response()->json([
                'success'   => true,
                'message'   => 'Scraper triggered! Data will update in ~3 minutes.',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'GitHub API error: ' . $response->status(),
            'body'    => $response->body(),
        ], 500);
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

            // Import all items into database - OPTIMIZED: batch insert instead of loop
            $itemsToInsert = [];
            $totalImported = 0;
            $now = now();

            foreach ($data['stores'] as $storeName => $items) {
                foreach ($items as $item) {
                    // Prepare for each platform (since they may have different availability)
                    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                        $itemsToInsert[] = [
                            'item_id' => $item['sku'] ?: 'unknown',
                            'shop_name' => $storeName,
                            'name' => $item['name'],
                            'sku' => $item['sku'],
                            'category' => $item['category'],
                            'price' => $item['price'],
                            'image_url' => $item['image_url'],
                            'is_available' => $item['is_available'],
                            'platform' => $platform,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $totalImported++;

                        // Insert in batches of 1000 to avoid memory issues
                        if (count($itemsToInsert) >= 1000) {
                            DB::table('items')->insert($itemsToInsert);
                            $itemsToInsert = [];
                        }
                    }
                }
            }

            // Insert any remaining items
            if (!empty($itemsToInsert)) {
                DB::table('items')->insert($itemsToInsert);
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

    // Trigger Items Scraper via GitHub Actions workflow_dispatch
    Route::post('/items/sync', function (Request $request) {
        $pat = env('GITHUB_PAT');

        if (!$pat) {
            return response()->json([
                'success' => false,
                'message' => 'GITHUB_PAT not configured.',
            ], 500);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $pat,
            'Accept'        => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->post('https://api.github.com/repos/developerokc5-max/resto-v3.5/actions/workflows/scrape-items.yml/dispatches', [
            'ref' => 'main',
        ]);

        if ($response->status() === 204) {
            return response()->json([
                'success'   => true,
                'message'   => 'Items scraper triggered! Data will update in ~10-15 minutes.',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'GitHub API error: ' . $response->status(),
            'body'    => $response->body(),
        ], 500);
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

    // Trigger Items Sync via GitHub Actions workflow_dispatch
    Route::post('/sync', function (Request $request) {
        $pat = env('GITHUB_PAT');

        if (!$pat) {
            return response()->json([
                'success' => false,
                'message' => 'GITHUB_PAT not configured.',
            ], 500);
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $pat,
            'Accept'        => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->post('https://api.github.com/repos/developerokc5-max/resto-v3.5/actions/workflows/scrape-items.yml/dispatches', [
            'ref' => 'main',
        ]);

        if ($response->status() === 204) {
            return response()->json([
                'success'   => true,
                'message'   => 'Items scraper triggered! Data will update in ~10-15 minutes.',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'GitHub API error: ' . $response->status(),
            'body'    => $response->body(),
        ], 500);
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

                // Invalidate cache to show real-time changes
                \App\Helpers\CacheOptimizationHelper::invalidateDashboardCaches();

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

    // Get all items with platform status - PAGINATED
    Route::get('/list', function (Request $request) {
        $perPage = (int)$request->query('per_page', 50);
        $page = (int)$request->query('page', 1);

        // Limit per_page to max 500 to prevent abuse
        $perPage = min($perPage, 500);
        $perPage = max($perPage, 1);

        $query = DB::table('items')
            ->select('id', 'item_id', 'shop_name', 'name', 'sku', 'category', 'price', 'is_available', 'platform')
            ->orderBy('shop_name')
            ->orderBy('name');

        $total = $query->count();
        $items = $query->forPage($page, $perPage)->get();
        $totalPages = ceil($total / $perPage);

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    });

    // Get items for specific shop - PAGINATED
    Route::get('/shop/{shopName}', function ($shopName, Request $request) {
        $perPage = (int)$request->query('per_page', 50);
        $page = (int)$request->query('page', 1);

        // Limit per_page to max 500 to prevent abuse
        $perPage = min($perPage, 500);
        $perPage = max($perPage, 1);

        $query = DB::table('items')
            ->where('shop_name', $shopName)
            ->orderBy('name');

        $total = $query->count();

        if ($total === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No items found for this shop',
            ], 404);
        }

        $items = $query->forPage($page, $perPage)->get();
        $totalPages = ceil($total / $perPage);

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
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

            // Invalidate cache to show real-time changes
            \App\Helpers\CacheOptimizationHelper::invalidateDashboardCaches();

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

// Alert check endpoint — called by GitHub Actions after scrape completes
Route::post('/alerts/check', function () {
    try {
        $alertService = new \App\Services\AlertService();
        $alertService->checkAndAlert();

        return response()->json([
            'success' => true,
            'message' => 'Alert check completed',
            'timestamp' => now('Asia/Singapore')->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Alert check failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Alert check failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});

// History snapshot — called by GitHub Actions after each scrape completes
// Reads current platform_status + items tables, writes fresh daily_history rows for today
Route::post('/history/snapshot', function () {
    try {
        // Auto-create daily_history table if migration didn't run (Neon cold-start guard)
        if (!\Illuminate\Support\Facades\Schema::hasTable('daily_history')) {
            \Illuminate\Support\Facades\Schema::create('daily_history', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->date('snapshot_date');
                $table->string('shop_id');
                $table->string('shop_name');
                $table->integer('platforms_online')->default(0);
                $table->integer('total_platforms')->default(3);
                $table->integer('total_offline_items')->default(0);
                $table->text('platform_data')->nullable();
                $table->timestamp('last_updated_at')->nullable();
                $table->timestamps();
                $table->unique(['snapshot_date', 'shop_id']);
                $table->index('snapshot_date');
            });
        }

        // Auto-create daily_scrape_log table if migration didn't run
        if (!\Illuminate\Support\Facades\Schema::hasTable('daily_scrape_log')) {
            \Illuminate\Support\Facades\Schema::create('daily_scrape_log', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->date('snapshot_date')->index();
                $table->timestamp('scanned_at');
                $table->integer('stores_total')->default(0);
                $table->integer('stores_offline')->default(0);
                $table->integer('items_offline')->default(0);
                $table->text('recoveries')->nullable();
                $table->timestamps();
            });
        }

        $nowSgt   = \Carbon\Carbon::now('Asia/Singapore');
        $todaySgt = $nowSgt->format('Y-m-d');
        $nowUtc   = $nowSgt->copy()->setTimezone('UTC');

        // Read current state from both tables (2 queries)
        $allPlatformStatus = DB::table('platform_status')->get()->groupBy('shop_id');

        $allOfflineItems = DB::table('items')
            ->where('is_available', false)
            ->whereIn('platform', ['grab', 'foodpanda', 'deliveroo'])
            ->get()
            ->groupBy(fn($item) => $item->shop_name . '|' . $item->platform);

        $insertRows = [];
        foreach ($allPlatformStatus as $shopId => $platforms) {
            $platformData    = [];
            $onlinePlatforms = 0;
            $totalOffline    = 0;
            $storeName       = $platforms->first()->store_name ?? '';

            foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                $status       = $platforms->firstWhere('platform', $platform);
                $offlineItems = $allOfflineItems->get($storeName . '|' . $platform, collect());
                $offlineCount = $offlineItems->count();
                $isOnline     = $status && $status->is_online;

                if ($isOnline) $onlinePlatforms++;
                $totalOffline += $offlineCount;

                $platformData[$platform] = [
                    'name'          => ucfirst($platform),
                    'status'        => $isOnline ? 'Online' : 'Offline',
                    'offline_items' => $offlineItems->map(fn($i) => [
                        'name'      => $i->name,
                        'sku'       => $i->sku       ?? null,
                        'category'  => $i->category  ?? null,
                        'price'     => $i->price      ?? null,
                        'image_url' => $i->image_url  ?? null,
                    ])->values()->toArray(),
                    'offline_count' => $offlineCount,
                ];
            }

            $insertRows[] = [
                'snapshot_date'       => $todaySgt,
                'shop_id'             => $shopId,
                'shop_name'           => $platforms->first()->store_name ?? $shopId,
                'platforms_online'    => $onlinePlatforms,
                'total_platforms'     => 3,
                'total_offline_items' => $totalOffline,
                'platform_data'       => json_encode($platformData),
                'last_updated_at'     => $nowUtc,
                'created_at'          => $nowUtc,
                'updated_at'          => $nowUtc,
            ];
        }

        if (!empty($insertRows)) {
            // Read previous state for today BEFORE overwriting — used for recovery detection
            $previousState = DB::table('daily_history')
                ->where('snapshot_date', $todaySgt)
                ->get()
                ->keyBy('shop_id');

            DB::table('daily_history')
                ->where('snapshot_date', $todaySgt)
                ->whereIn('shop_id', array_column($insertRows, 'shop_id'))
                ->delete();
            DB::table('daily_history')->insert($insertRows);

            // Detect recoveries: stores that were offline last scrape but are online now
            $recoveries = [];
            foreach ($insertRows as $row) {
                $prev = $previousState->get($row['shop_id']);
                if ($prev && $prev->platforms_online < $prev->total_platforms && $row['platforms_online'] >= $row['total_platforms']) {
                    $recoveries[] = ['shop_id' => $row['shop_id'], 'shop_name' => $row['shop_name']];
                }
            }

            // Log this scrape event
            DB::table('daily_scrape_log')->insert([
                'snapshot_date'  => $todaySgt,
                'scanned_at'     => $nowUtc,
                'stores_total'   => count($insertRows),
                'stores_offline' => count(array_filter($insertRows, fn($r) => $r['platforms_online'] < $r['total_platforms'])),
                'items_offline'  => array_sum(array_column($insertRows, 'total_offline_items')),
                'recoveries'     => json_encode($recoveries),
                'created_at'     => $nowUtc,
                'updated_at'     => $nowUtc,
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Daily history snapshot updated',
            'date'      => $todaySgt,
            'stores'    => count($insertRows),
            'timestamp' => $nowUtc->toIso8601String(),
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('History snapshot failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Snapshot failed: ' . $e->getMessage(),
        ], 500);
    }
});

// Store logs bulk snapshot — called by GitHub Actions after each scrape
// Saves today's entry in store_status_logs for EVERY shop automatically
Route::post('/store-logs/snapshot', function () {
    try {
        $nowSgt          = \Carbon\Carbon::now('Asia/Singapore');
        $todayUtcStart   = $nowSgt->copy()->startOfDay()->setTimezone('UTC');
        $tomorrowUtcStart = $todayUtcStart->copy()->addDay();

        // 2 queries to get all data at once
        $allPlatformStatus = DB::table('platform_status')->get()->groupBy('shop_id');

        $allOfflineItems = DB::table('items')
            ->where('is_available', false)
            ->whereIn('platform', ['grab', 'foodpanda', 'deliveroo'])
            ->get()
            ->groupBy(fn($item) => $item->shop_id . '|' . $item->platform);

        $insertRows = [];
        $shopIds    = [];

        foreach ($allPlatformStatus as $shopId => $platforms) {
            $platformData    = [];
            $onlinePlatforms = 0;
            $totalOffline    = 0;

            foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                $status       = $platforms->firstWhere('platform', $platform);
                $offlineItems = $allOfflineItems->get($shopId . '|' . $platform, collect());
                $offlineCount = $offlineItems->count();
                $isOnline     = $status && $status->is_online;

                if ($isOnline) $onlinePlatforms++;
                $totalOffline += $offlineCount;

                $platformData[$platform] = [
                    'name'          => ucfirst($platform),
                    'status'        => $isOnline ? 'Online' : 'Offline',
                    'last_checked'  => $status ? $status->last_checked_at : null,
                    'offline_items' => $offlineItems->map(fn($i) => [
                        'name'      => $i->name,
                        'sku'       => $i->sku       ?? null,
                        'category'  => $i->category  ?? null,
                        'price'     => $i->price      ?? null,
                        'image_url' => $i->image_url  ?? null,
                    ])->values()->toArray(),
                    'offline_count' => $offlineCount,
                ];
            }

            $shopIds[]    = $shopId;
            $insertRows[] = [
                'shop_id'             => $shopId,
                'shop_name'           => $platforms->first()->store_name ?? $shopId,
                'platforms_online'    => $onlinePlatforms,
                'total_platforms'     => 3,
                'total_offline_items' => $totalOffline,
                'platform_data'       => json_encode($platformData),
                'logged_at'           => $todayUtcStart,
                'created_at'          => $todayUtcStart,
                'updated_at'          => $todayUtcStart,
            ];
        }

        if (!empty($insertRows)) {
            DB::table('store_status_logs')
                ->whereIn('shop_id', $shopIds)
                ->whereBetween('logged_at', [$todayUtcStart, $tomorrowUtcStart])
                ->delete();
            DB::table('store_status_logs')->insert($insertRows);
        }

        return response()->json([
            'success' => true,
            'stores'  => count($insertRows),
            'date'    => $nowSgt->format('Y-m-d'),
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Health check
Route::get('/health', function () {
    // Single consolidated query instead of 4 separate queries
    $stats = DB::table('platform_status')
        ->select(
            DB::raw('MAX(last_checked_at) as last_sync'),
            DB::raw('COUNT(DISTINCT shop_id) as total_shops'),
            DB::raw('SUM(CASE WHEN is_online = true THEN 1 ELSE 0 END) as online_platforms'),
            DB::raw('COUNT(*) as total_platforms')
        )
        ->first();

    $totalPlatforms = $stats->total_platforms ?? 0;
    $onlinePlatforms = $stats->online_platforms ?? 0;

    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'hybrid_system' => [
            'last_scrape' => $stats->last_sync,
            'shops_monitored' => $stats->total_shops ?? 0,
            'platforms_online' => $onlinePlatforms,
            'platforms_total' => $totalPlatforms,
            'online_percentage' => $totalPlatforms > 0 ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
        ],
    ]);
});

// ── Scan Alert Email — called by GitHub Actions after platform scrape ──────
// Sends a Resend email summary if any stores have issues.
// Rate-limited to once per 55 minutes to avoid spam.
Route::post('/alert/email', function () {
    try {
        $apiKey    = env('RESEND_API_KEY');
        $fromEmail = env('ALERT_FROM_EMAIL', 'HawkerOps <onboarding@resend.dev>');
        $toRaw     = env('ALERT_TO_EMAILS', '');
        $toEmails  = array_values(array_filter(array_map('trim', explode(',', $toRaw))));

        if (!$apiKey || empty($toEmails)) {
            return response()->json(['success' => false, 'message' => 'Email alert not configured — set RESEND_API_KEY and ALERT_TO_EMAILS'], 400);
        }

        // Rate limit: max one alert email per 55 minutes
        $cacheKey = 'hawkerops_alert_last_sent';
        $lastSent = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($lastSent && \Carbon\Carbon::parse($lastSent)->diffInMinutes(now()) < 55) {
            return response()->json(['success' => false, 'message' => 'Rate limited — alert sent recently', 'next_in_minutes' => 55 - \Carbon\Carbon::parse($lastSent)->diffInMinutes(now())], 429);
        }

        $nowSgt = \Carbon\Carbon::now('Asia/Singapore');

        // Count stores with issues
        $storesWithIssues = DB::table('platform_status')
            ->where('is_online', false)
            ->distinct('shop_id')
            ->count('shop_id');

        $totalStores = DB::table('platform_status')
            ->distinct('shop_id')
            ->count('shop_id');

        $healthyStores = $totalStores - $storesWithIssues;

        // Platform breakdown
        $grabOff = DB::table('platform_status')
            ->where('platform', 'grab')->where('is_online', false)->count();
        $fpOff = DB::table('platform_status')
            ->where('platform', 'foodpanda')->where('is_online', false)->count();
        $delOff = DB::table('platform_status')
            ->where('platform', 'deliveroo')->where('is_online', false)->count();

        // Menu items offline
        $itemsOffline = DB::table('items')
            ->where('is_available', false)
            ->whereIn('platform', ['grab', 'foodpanda', 'deliveroo'])
            ->count();

        // Skip email if everything is fine
        if ($storesWithIssues === 0) {
            return response()->json(['success' => true, 'message' => 'All clear — no alert needed']);
        }

        // Top 8 stores with issues
        $issueStores = DB::table('platform_status')
            ->where('is_online', false)
            ->select('store_name', DB::raw('COUNT(*) as platforms_offline'))
            ->groupBy('store_name')
            ->orderByDesc('platforms_offline')
            ->limit(8)
            ->get();

        $dateStr   = $nowSgt->format('D, M j, Y');
        $timeStr   = $nowSgt->format('g:i A') . ' SGT';
        $reportUrl = 'https://resto-v3-5.onrender.com/history/' . $nowSgt->format('Y-m-d');
        $subject   = "⚠️ HawkerOps — {$storesWithIssues} stores with issues · {$dateStr}";

        // Build HTML email inline (no blade dependency)
        $storeRows = '';
        foreach ($issueStores as $s) {
            $pl = $s->platforms_offline;
            $storeRows .= "<tr>
              <td style='padding:8px 16px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;'>{$s->store_name}</td>
              <td style='padding:8px 16px;font-size:13px;color:#ef4444;font-weight:700;text-align:right;border-bottom:1px solid #f1f5f9;'>{$pl} platform" . ($pl > 1 ? 's' : '') . " offline</td>
            </tr>";
        }
        $moreText = $storesWithIssues > 8 ? "<p style='margin:12px 16px 0;font-size:12px;color:#94a3b8;'>+ " . ($storesWithIssues - 8) . " more stores with issues</p>" : '';

        $html = "<!DOCTYPE html>
<html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>HawkerOps Alert</title></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;'>
  <div style='max-width:560px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

    {{-- Header --}}
    <div style='background:#ef4444;padding:24px 28px;'>
      <p style='margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:rgba(255,255,255,0.75);text-transform:uppercase;'>HawkerOps Alert</p>
      <h1 style='margin:0;font-size:22px;font-weight:800;color:#ffffff;'>{$storesWithIssues} Stores with Issues</h1>
      <p style='margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.85);'>{$dateStr} &nbsp;·&nbsp; {$timeStr}</p>
    </div>

    {{-- Stats row --}}
    <div style='display:flex;border-bottom:1px solid #f1f5f9;'>
      <div style='flex:1;padding:20px 16px;text-align:center;border-right:1px solid #f1f5f9;'>
        <p style='margin:0;font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;'>Stores Scanned</p>
        <p style='margin:6px 0 0;font-size:28px;font-weight:800;color:#0f172a;'>{$totalStores}</p>
      </div>
      <div style='flex:1;padding:20px 16px;text-align:center;border-right:1px solid #f1f5f9;'>
        <p style='margin:0;font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;'>w/ Issues</p>
        <p style='margin:6px 0 0;font-size:28px;font-weight:800;color:#f59e0b;'>{$storesWithIssues}</p>
      </div>
      <div style='flex:1;padding:20px 16px;text-align:center;'>
        <p style='margin:0;font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;'>Items Offline</p>
        <p style='margin:6px 0 0;font-size:28px;font-weight:800;color:#ef4444;'>{$itemsOffline}</p>
      </div>
    </div>

    {{-- Platform breakdown --}}
    <div style='padding:16px 16px 8px;display:flex;gap:8px;'>
      <div style='flex:1;padding:12px;background:#f0fdf4;border-radius:10px;text-align:center;'>
        <p style='margin:0;font-size:11px;color:#16a34a;font-weight:700;'>Grab</p>
        <p style='margin:4px 0 0;font-size:18px;font-weight:800;color:#15803d;'>{$grabOff} off</p>
      </div>
      <div style='flex:1;padding:12px;background:#fdf2f8;border-radius:10px;text-align:center;'>
        <p style='margin:0;font-size:11px;color:#be185d;font-weight:700;'>FoodPanda</p>
        <p style='margin:4px 0 0;font-size:18px;font-weight:800;color:#9d174d;'>{$fpOff} off</p>
      </div>
      <div style='flex:1;padding:12px;background:#ecfeff;border-radius:10px;text-align:center;'>
        <p style='margin:0;font-size:11px;color:#0e7490;font-weight:700;'>Deliveroo</p>
        <p style='margin:4px 0 0;font-size:18px;font-weight:800;color:#155e75;'>{$delOff} off</p>
      </div>
    </div>

    {{-- Issue stores table --}}
    <div style='padding:16px 16px 8px;'>
      <p style='margin:0 0 8px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;'>Affected Stores</p>
      <table width='100%' cellpadding='0' cellspacing='0' style='border-radius:10px;overflow:hidden;border:1px solid #f1f5f9;'>
        {$storeRows}
      </table>
      {$moreText}
    </div>

    {{-- CTA button --}}
    <div style='padding:20px 16px 28px;text-align:center;'>
      <a href='{$reportUrl}'
         style='display:inline-block;padding:12px 32px;background:#0f172a;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;border-radius:10px;letter-spacing:0.3px;'>
        View Full Report →
      </a>
    </div>

    {{-- Footer --}}
    <div style='padding:16px;background:#f8fafc;border-top:1px solid #f1f5f9;text-align:center;'>
      <p style='margin:0;font-size:11px;color:#cbd5e1;'>HawkerOps · Automated scan alert · {$timeStr}</p>
    </div>

  </div>
</body></html>";

        // Send via Resend API
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.resend.com/emails', [
            'from'    => $fromEmail,
            'to'      => $toEmails,
            'subject' => $subject,
            'html'    => $html,
        ]);

        if ($response->successful()) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, now()->toIso8601String(), 3600);
            return response()->json([
                'success'  => true,
                'message'  => 'Alert email sent',
                'to'       => $toEmails,
                'subject'  => $subject,
                'resend_id' => $response->json('id'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Resend API error',
            'details' => $response->json(),
        ], 500);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Alert email failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});
