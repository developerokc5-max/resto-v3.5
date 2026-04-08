<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PlatformStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ShopHelper;
use App\Http\Controllers\Api\MonitorController;

/**
 * API Routes for Hybrid System
 */

// Lightweight sync-timestamp check used by smartReload() to skip full-page
// fetches when no new data has arrived since the last reload.
// Returns a unix timestamp — JS compares it to _lastSyncTs to decide whether
// to pay for a full HTML fetch or bail out early.
Route::get('/last-sync', function () {
    $ts = Cache::remember('last_sync_unix', 60, function () {
        $candidates = array_filter([
            DB::table('platform_status')->max('last_checked_at'),
            DB::table('restosuite_item_snapshots')->max('updated_at'),
        ]);
        if (empty($candidates)) return 0;
        return \Carbon\Carbon::parse(max($candidates))->timestamp;
    });
    return response()->json(['timestamp' => $ts]);
});

// Clear page cache — called by Reload button before fetching fresh HTML
Route::post('/cache/clear', function () {
    $keys = [
        'shop_map',
        'stores_page_data',
        'platforms_page_data',
        'offline_items_page',
        'shops_name_list',
        'items_categories',
        'dashboard_kpis_consolidated',
        'alert_metrics_consolidated',
        'store_stats_consolidated',
        'offline_items_by_shop_platform',
        'all_platform_statuses',
        'alerts_db_data',
        'reports_daily_trends',
        'reports_platform_reliability',
        'reports_item_performance_v2',
        'store_comparison_db',
        'scraper_status_db',
        'scraper_logs_recent',
        'last_sync_unix',
    ];

    foreach ($keys as $key) {
        Cache::forget($key);
    }

    return response()->json(['ok' => true]);
});

// Resolve stale alerts — marks open alerts older than X days as auto-resolved
Route::post('/alerts/resolve-stale', function (\Illuminate\Http\Request $request) {
    $days = max(1, (int) $request->input('days', 7));
    $cutoff = \Carbon\Carbon::now()->subDays($days);
    $count = DB::table('alert_logs')
        ->where('type', 'offline')
        ->whereNull('recovered_at')
        ->where('alerted_at', '<', $cutoff)
        ->update([
            'recovered_at'     => \Carbon\Carbon::now(),
            'downtime_minutes' => DB::raw("EXTRACT(EPOCH FROM (NOW() - alerted_at)) / 60"),
            'updated_at'       => \Carbon\Carbon::now(),
        ]);
    Cache::forget('bell_alert_count');
    Cache::forget('alerts_db_data');
    return response()->json(['ok' => true, 'resolved' => $count]);
});

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
            // Record manual trigger so the next scheduled run skips itself
            try {
                \DB::table('scraper_manual_triggers')->insert([
                    'scraper_type' => 'platform',
                    'triggered_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Non-fatal — don't block the response
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Scraper triggered! Data will update in ~3 minutes. Next scheduled run will be skipped.',
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
        // Secret token guard — rejects requests without the correct header
        $secret = env('SCRAPER_SECRET');
        if (!$secret || $request->header('X-Scraper-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Increase timeout to 30 minutes (scraping all stores takes time)
        set_time_limit(1800);

        try {
            // Run the BULLETPROOF items scraper
            $scriptPath = base_path('_archive/scrapers/scrape_items_bulletproof.py');
            $command = "timeout 1800 python \"{$scriptPath}\" 2>&1";

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

            if (!isset($data['stores']) || !is_array($data['stores'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scraper output missing stores data',
                    'output' => $rawOutput,
                ], 500);
            }

            // Save to cache
            $cacheFile = storage_path('app/items_data_cache.json');
            file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));

            // Clear and re-import inside a transaction so data is never left empty on failure
            $totalImported = 0;
            DB::transaction(function () use ($data, &$totalImported) {
                DB::table('items')->truncate();

                // Import all items into database - OPTIMIZED: batch insert instead of loop
                $itemsToInsert = [];
                $now = now();

                foreach ($data['stores'] as $storeName => $items) {
                    foreach ($items as $item) {
                        foreach (['grab', 'foodpanda'] as $platform) {
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

                            if (count($itemsToInsert) >= 1000) {
                                DB::table('items')->insert($itemsToInsert);
                                $itemsToInsert = [];
                            }
                        }
                    }
                }

                if (!empty($itemsToInsert)) {
                    DB::table('items')->insert($itemsToInsert);
                }
            }); // end DB::transaction

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
            // Record manual trigger so the next scheduled run skips itself
            try {
                \DB::table('scraper_manual_triggers')->insert([
                    'scraper_type' => 'items',
                    'triggered_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Non-fatal
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Items scraper triggered! Data will update in ~10-15 minutes. Next scheduled run will be skipped.',
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
            // Record manual trigger so the next scheduled run skips itself
            try {
                \DB::table('scraper_manual_triggers')->insert([
                    'scraper_type' => 'items',
                    'triggered_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Non-fatal
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Items scraper triggered! Data will update in ~10-15 minutes. Next scheduled run will be skipped.',
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
                'platform' => 'required|string|in:grab,foodpanda',
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
    $secret = env('ACTIONS_SECRET');
    if ($secret && !hash_equals($secret, (string) request()->header('X-Actions-Secret', ''))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
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
    $secret = env('ACTIONS_SECRET');
    if ($secret && !hash_equals($secret, (string) request()->header('X-Actions-Secret', ''))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
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
            ->whereIn('platform', ['grab', 'foodpanda'])
            ->get()
            ->groupBy(fn($item) => $item->shop_id . '|' . $item->platform);

        $insertRows = [];
        foreach ($allPlatformStatus as $shopId => $platforms) {
            $platformData    = [];
            $onlinePlatforms = 0;
            $totalOffline    = 0;

            foreach (['grab', 'foodpanda'] as $platform) {
                $status       = $platforms->firstWhere('platform', $platform);
                $offlineItems = $allOfflineItems->get($shopId . '|' . $platform, collect());
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
                'total_platforms'     => 2,
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

            // Prune scrape log rows older than 30 days
            DB::table('daily_scrape_log')
                ->where('snapshot_date', '<', $nowSgt->copy()->subDays(30)->format('Y-m-d'))
                ->delete();
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
    $secret = env('ACTIONS_SECRET');
    if ($secret && !hash_equals($secret, (string) request()->header('X-Actions-Secret', ''))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    try {
        $nowSgt          = \Carbon\Carbon::now('Asia/Singapore');
        $todayUtcStart   = $nowSgt->copy()->startOfDay()->setTimezone('UTC');
        $tomorrowUtcStart = $todayUtcStart->copy()->addDay();

        // 2 queries to get all data at once
        $allPlatformStatus = DB::table('platform_status')->get()->groupBy('shop_id');

        $allOfflineItems = DB::table('items')
            ->where('is_available', false)
            ->whereIn('platform', ['grab', 'foodpanda'])
            ->get()
            ->groupBy(fn($item) => $item->shop_id . '|' . $item->platform);

        $insertRows = [];
        $shopIds    = [];

        foreach ($allPlatformStatus as $shopId => $platforms) {
            $platformData    = [];
            $onlinePlatforms = 0;
            $totalOffline    = 0;

            foreach (['grab', 'foodpanda'] as $platform) {
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
                'total_platforms'     => 2,
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

// ========================================
// READ-ONLY API FOR MISSING PAGES
// ========================================

// GET /api/alerts — same logic as /alerts page
Route::get('/alerts', function () {
    $dbData = \Illuminate\Support\Facades\Cache::remember('alerts_db_data', 300, function () {
        $allPlatformCounts = DB::table('platform_status')
            ->selectRaw('shop_id, COUNT(*) as total_platforms')
            ->groupBy('shop_id')
            ->pluck('total_platforms', 'shop_id');

        return [
            'allPlatformCounts' => $allPlatformCounts,
            'offlineCounts'     => DB::table('platform_status')
                ->selectRaw('shop_id, COUNT(*) as offline_count, MAX(last_checked_at) as last_checked')
                ->where('is_online', false)
                ->groupBy('shop_id')
                ->get()
                ->keyBy('shop_id'),
            'storeNameMap'      => DB::table('platform_status')
                ->selectRaw('shop_id, MIN(store_name) as store_name')
                ->groupBy('shop_id')
                ->pluck('store_name', 'shop_id'),
            'platformStats'     => DB::table('platform_status')
                ->selectRaw('platform,
                    SUM(CASE WHEN is_online = false THEN 1 ELSE 0 END) as offline_count,
                    SUM(CASE WHEN is_online = true  THEN 1 ELSE 0 END) as online_count,
                    COUNT(*) as total,
                    MAX(last_checked_at) as last_checked')
                ->groupBy('platform')
                ->get()
                ->keyBy('platform'),
            'storesWithOfflineItems' => DB::table('items')
                ->selectRaw('shop_name,
                    SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) as offline_count,
                    COUNT(*) as total_items,
                    MAX(updated_at) as last_updated')
                ->groupBy('shop_name')
                ->havingRaw('SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) > 20')
                ->orderByRaw('offline_count DESC')
                ->limit(8)
                ->get(),
            'latestScrape' => DB::table('platform_status')->max('last_checked_at'),
            'totalStores'  => DB::table('platform_status')->distinct()->count('shop_id'),
        ];
    });

    $allPlatformCounts      = $dbData['allPlatformCounts'];
    $offlineCounts          = $dbData['offlineCounts'];
    $storeNameMap           = $dbData['storeNameMap'];
    $platformStats          = $dbData['platformStats'];
    $storesWithOfflineItems = $dbData['storesWithOfflineItems'];
    $latestScrape           = $dbData['latestScrape'];
    $totalStores            = $dbData['totalStores'];

    $alerts = [];

    // Stores where ALL platforms offline
    $fullyOfflineStores = [];
    foreach ($allPlatformCounts as $shopId => $total) {
        $offlineRow = $offlineCounts->get($shopId);
        if ($offlineRow && $offlineRow->offline_count >= $total) {
            $fullyOfflineStores[] = [
                'shop_id'      => $shopId,
                'name'         => $storeNameMap->get($shopId) ?? $shopId,
                'last_checked' => $offlineRow->last_checked,
            ];
        }
    }
    if (!empty($fullyOfflineStores)) {
        usort($fullyOfflineStores, fn($a, $b) => strcmp($a['last_checked'] ?? '', $b['last_checked'] ?? ''));
        $oldest = $fullyOfflineStores[0]['last_checked'] ?? null;
        $alerts[] = [
            'type'         => 'critical',
            'title'        => count($fullyOfflineStores) === 1
                ? $fullyOfflineStores[0]['name'] . ' — All Platforms Offline'
                : count($fullyOfflineStores) . ' Stores Completely Offline',
            'stores'       => $fullyOfflineStores,
            'last_checked' => $oldest,
            'time_human'   => $oldest ? \Carbon\Carbon::parse($oldest)->diffForHumans() : null,
        ];
    }

    // Per-platform offline counts
    foreach ($platformStats as $platform => $stat) {
        if ($stat->offline_count >= 3) {
            $pct = $stat->total > 0 ? round(($stat->offline_count / $stat->total) * 100) : 0;
            $alerts[] = [
                'type'         => $pct >= 50 ? 'critical' : 'warning',
                'title'        => ucfirst($platform) . ' — ' . $stat->offline_count . ' Stores Offline',
                'platform'     => $platform,
                'offline'      => (int) $stat->offline_count,
                'total'        => (int) $stat->total,
                'pct'          => $pct,
                'last_checked' => $stat->last_checked,
                'time_human'   => $stat->last_checked ? \Carbon\Carbon::parse($stat->last_checked)->diffForHumans() : null,
            ];
        }
    }

    // Stores with many offline items
    foreach ($storesWithOfflineItems as $store) {
        $pct = $store->total_items > 0 ? round(($store->offline_count / $store->total_items) * 100) : 0;
        $alerts[] = [
            'type'         => $pct >= 70 ? 'critical' : 'warning',
            'title'        => $store->shop_name . ' — ' . $store->offline_count . ' Items Unavailable',
            'shop_name'    => $store->shop_name,
            'offline_items'=> (int) $store->offline_count,
            'total_items'  => (int) $store->total_items,
            'pct'          => $pct,
            'last_updated' => $store->last_updated,
            'time_human'   => $store->last_updated ? \Carbon\Carbon::parse($store->last_updated)->diffForHumans() : null,
        ];
    }

    // Stale data warning
    if ($latestScrape) {
        $minutesAgo = \Carbon\Carbon::parse($latestScrape)->diffInMinutes(now());
        if ($minutesAgo > 120) {
            $alerts[] = [
                'type'        => 'info',
                'title'       => 'Data May Be Stale',
                'minutes_ago' => $minutesAgo,
                'time_human'  => \Carbon\Carbon::parse($latestScrape)->diffForHumans(),
            ];
        }
    }

    $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
    usort($alerts, fn($a, $b) => ($order[$a['type']] ?? 9) <=> ($order[$b['type']] ?? 9));

    $platformSummary = [];
    foreach ($platformStats as $platform => $stat) {
        $platformSummary[] = [
            'platform' => $platform,
            'online'   => (int) $stat->online_count,
            'offline'  => (int) $stat->offline_count,
            'total'    => (int) $stat->total,
        ];
    }

    return response()->json([
        'success'         => true,
        'alerts'          => $alerts,
        'stats'           => [
            'critical' => count(array_filter($alerts, fn($a) => $a['type'] === 'critical')),
            'warnings' => count(array_filter($alerts, fn($a) => $a['type'] === 'warning')),
            'info'     => count(array_filter($alerts, fn($a) => $a['type'] === 'info')),
            'total'    => $totalStores,
            'healthy'  => max(0, $totalStores - count($fullyOfflineStores)),
        ],
        'platform_summary'=> $platformSummary,
        'latest_scrape'   => $latestScrape,
    ]);
});

// GET /api/history — list of all snapshot dates with summaries
Route::get('/history', function () {
    if (!\Illuminate\Support\Facades\Schema::hasTable('daily_history')) {
        return response()->json(['success' => true, 'data' => [], 'message' => 'No history yet']);
    }

    $todaySgt = \Carbon\Carbon::now('Asia/Singapore')->format('Y-m-d');

    $dateSummaries = DB::table('daily_history')
        ->selectRaw("snapshot_date,
            COUNT(*) as total_stores,
            SUM(CASE WHEN platforms_online < total_platforms THEN 1 ELSE 0 END) as stores_with_issues,
            SUM(total_offline_items) as total_offline_items,
            MAX(last_updated_at) as last_updated_at")
        ->groupBy('snapshot_date')
        ->orderByDesc('snapshot_date')
        ->get();

    $scrapeCounts = \Illuminate\Support\Facades\Schema::hasTable('daily_scrape_log')
        ? DB::table('daily_scrape_log')
            ->selectRaw('snapshot_date, COUNT(*) as scrape_count')
            ->groupBy('snapshot_date')
            ->get()
            ->keyBy('snapshot_date')
        : collect();

    $data = $dateSummaries->map(fn($day) => [
        'date'                => $day->snapshot_date,
        'is_today'            => $day->snapshot_date === $todaySgt,
        'total_stores'        => (int) $day->total_stores,
        'stores_with_issues'  => (int) $day->stores_with_issues,
        'total_offline_items' => (int) $day->total_offline_items,
        'last_updated_at'     => $day->last_updated_at,
        'scrape_count'        => (int) ($scrapeCounts->get($day->snapshot_date)?->scrape_count ?? 0),
    ]);

    return response()->json(['success' => true, 'data' => $data]);
});

// GET /api/history/{date} — all stores for a specific date
Route::get('/history/{date}', function ($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return response()->json(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD'], 422);
    }
    if (!\Illuminate\Support\Facades\Schema::hasTable('daily_history')) {
        return response()->json(['success' => false, 'message' => 'No history data'], 404);
    }

    $stores = DB::table('daily_history')
        ->where('snapshot_date', $date)
        ->orderByRaw('CASE WHEN platforms_online < total_platforms OR total_offline_items > 0 THEN 0 ELSE 1 END')
        ->orderBy('shop_name')
        ->get()
        ->map(function ($store) {
            $store->platform_data = json_decode($store->platform_data, true);
            return $store;
        });

    if ($stores->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No data for this date'], 404);
    }

    $scrapeLog = \Illuminate\Support\Facades\Schema::hasTable('daily_scrape_log')
        ? DB::table('daily_scrape_log')
            ->where('snapshot_date', $date)
            ->orderBy('scanned_at')
            ->get()
            ->map(fn($log) => [
                'scanned_at'     => $log->scanned_at,
                'scanned_at_sgt' => \Carbon\Carbon::parse($log->scanned_at)->setTimezone('Asia/Singapore')->toIso8601String(),
                'stores_total'   => $log->stores_total,
                'stores_offline' => $log->stores_offline,
                'items_offline'  => $log->items_offline,
                'recoveries'     => json_decode($log->recoveries ?? '[]', true) ?: [],
            ])
        : collect();

    return response()->json([
        'success'    => true,
        'date'       => $date,
        'is_today'   => $date === \Carbon\Carbon::now('Asia/Singapore')->format('Y-m-d'),
        'stores'     => $stores,
        'scrape_log' => $scrapeLog,
        'last_updated' => DB::table('daily_history')->where('snapshot_date', $date)->max('last_updated_at'),
    ]);
});

// GET /api/store/{shopId} — store detail with platform status + grouped items
Route::get('/store/{shopId}', function ($shopId) {
    $shop = DB::table('shops')->where('shop_id', $shopId)->first();
    if (!$shop) {
        return response()->json(['success' => false, 'message' => 'Store not found'], 404);
    }

    $items = DB::table('items')
        ->where('shop_name', $shop->shop_name)
        ->orderBy('category')
        ->orderBy('name')
        ->get();

    $groupedItems = [];
    foreach ($items as $item) {
        $key = $item->name . '|' . $item->category;
        if (!isset($groupedItems[$key])) {
            $groupedItems[$key] = [
                'name'       => $item->name,
                'category'   => $item->category,
                'image_url'  => $item->image_url,
                'price'      => $item->price,
                'platforms'  => [],
                'all_active' => true,
            ];
        }
        $groupedItems[$key]['platforms'][$item->platform] = [
            'is_available' => (bool) $item->is_available,
            'price'        => $item->price,
        ];
        if (!$item->is_available) {
            $groupedItems[$key]['all_active'] = false;
        }
    }

    $platformStatus = DB::table('platform_status')
        ->where('store_name', $shop->shop_name)
        ->get()
        ->keyBy('platform');

    return response()->json([
        'success'         => true,
        'shop'            => $shop,
        'platform_status' => $platformStatus,
        'items'           => array_values($groupedItems),
        'summary'         => [
            'total_items'   => count($groupedItems),
            'offline_items' => count(array_filter($groupedItems, fn($i) => !$i['all_active'])),
        ],
    ]);
});

// GET /api/reports/daily-trends — daily trends data
Route::get('/reports/daily-trends', function (\Illuminate\Http\Request $request) {
    $now       = \Carbon\Carbon::now('Asia/Singapore');
    $startDate = $request->get('start', $now->copy()->subDays(6)->toDateString());
    $endDate   = $request->get('end', $now->toDateString());

    $trends = \Illuminate\Support\Facades\Cache::remember('reports_daily_trends', 300, function () use ($now) {
        $platformStats  = DB::table('platform_status')
            ->selectRaw('platform, AVG(CASE WHEN is_online = true THEN 100 ELSE 0 END) as uptime')
            ->groupBy('platform')->get();
        $todaySgt       = $now->toDateString();
        $peakHourData   = DB::table('store_status_logs')
            ->selectRaw("EXTRACT(HOUR FROM logged_at + INTERVAL '8 hours')::int as hour, COUNT(*) as count")
            ->whereRaw("DATE(logged_at + INTERVAL '8 hours') = ?", [$todaySgt])
            ->groupBy('hour')->orderBy('count', 'desc')->first();
        $h24            = $peakHourData ? (int)$peakHourData->hour : null;
        return [
            'avg_uptime'  => round($platformStats->avg('uptime') ?? 0, 1),
            'avg_offline' => DB::table('items')->where('is_available', false)->count(),
            'peak_hour'   => $h24 !== null ? sprintf('%d %s', $h24 % 12 ?: 12, $h24 >= 12 ? 'PM' : 'AM') : 'N/A',
            'incidents'   => DB::table('store_status_logs')
                ->whereRaw("DATE(logged_at + INTERVAL '8 hours') = ?", [$todaySgt])->count(),
        ];
    });

    $dailyData = DB::table('daily_history')
        ->selectRaw('snapshot_date, SUM(total_offline_items) as total_offline')
        ->whereBetween('snapshot_date', [$startDate, $endDate])
        ->groupBy('snapshot_date')->orderBy('snapshot_date')->get();

    $platformUptimeData = DB::table('daily_history')
        ->selectRaw("snapshot_date,
            ROUND(AVG(CASE WHEN (platform_data::jsonb)->'grab'->>'status'      = 'Online' THEN 100.0 ELSE 0 END), 1) as grab_uptime,
            ROUND(AVG(CASE WHEN (platform_data::jsonb)->'foodpanda'->>'status'  = 'Online' THEN 100.0 ELSE 0 END), 1) as foodpanda_uptime")
        ->whereBetween('snapshot_date', [$startDate, $endDate])
        ->whereNotNull('platform_data')->where('platform_data', '!=', '')
        ->groupBy('snapshot_date')->orderBy('snapshot_date')->get();

    return response()->json([
        'success'             => true,
        'trends'              => $trends,
        'daily_data'          => $dailyData,
        'platform_uptime'     => $platformUptimeData,
        'date_range'          => ['start' => $startDate, 'end' => $endDate],
    ]);
});

// GET /api/reports/platform-reliability
Route::get('/reports/platform-reliability', function () {
    $platformData = \Illuminate\Support\Facades\Cache::remember('reports_platform_reliability', 300, function () {
        $statuses = DB::table('platform_status')
            ->select('platform',
                DB::raw('COUNT(*) as total_stores'),
                DB::raw('SUM(CASE WHEN is_online = true THEN 1 ELSE 0 END) as online_stores'))
            ->groupBy('platform')->get()->keyBy('platform');

        $data = [];
        foreach (['grab', 'foodpanda'] as $p) {
            $s = $statuses->get($p);
            $total  = $s->total_stores  ?? 0;
            $online = $s->online_stores ?? 0;
            $data[$p] = [
                'platform'      => $p,
                'name'          => ucfirst($p),
                'uptime'        => $total > 0 ? round(($online / $total) * 100, 1) : 100,
                'online_stores' => (int) $online,
                'total_stores'  => (int) $total,
            ];
        }
        return $data;
    });

    return response()->json(['success' => true, 'data' => array_values($platformData)]);
});

// GET /api/reports/item-performance
Route::get('/reports/item-performance', function () {
    $reportData = \Illuminate\Support\Facades\Cache::remember('reports_item_performance_v2', 300, function () {
        $totalItems   = DB::table('items')
            ->selectRaw("COUNT(DISTINCT name || '|' || shop_name || '|' || platform) as total")->first()->total;
        $offlineItems = DB::table('items')->where('is_available', false)->count();
        $onlineItems  = $totalItems - $offlineItems;

        $sevenDaysAgo  = \Carbon\Carbon::now('Asia/Singapore')->subDays(7)->toDateTimeString();
        $hasHistory    = DB::table('item_status_history')->where('changed_at', '>=', $sevenDaysAgo)->exists();

        $topOfflineItems = $hasHistory
            ? DB::select("
                WITH ranked AS (
                    SELECT item_name, shop_name, platform, is_available, changed_at,
                        LEAD(changed_at) OVER (PARTITION BY item_name, shop_name, platform ORDER BY changed_at) as next_change
                    FROM item_status_history WHERE changed_at >= :since
                ),
                offline_durations AS (
                    SELECT item_name, shop_name, platform,
                        EXTRACT(EPOCH FROM (COALESCE(next_change, NOW()) - changed_at)) / 3600.0 as hours
                    FROM ranked WHERE is_available = false
                )
                SELECT item_name as name, shop_name, platform,
                    COUNT(*) as offline_count,
                    ROUND(AVG(hours)::numeric, 1) as avg_hours_offline
                FROM offline_durations
                GROUP BY item_name, shop_name, platform
                ORDER BY offline_count DESC LIMIT 10
            ", ['since' => $sevenDaysAgo])
            : DB::table('items')->where('is_available', false)
                ->selectRaw("name, shop_name, platform, COUNT(*) as offline_count, 0 as avg_hours_offline")
                ->groupBy('name', 'shop_name', 'platform')
                ->orderBy('offline_count', 'desc')->limit(10)->get();

        $categoryData = DB::table('items')
            ->selectRaw("category,
                COUNT(DISTINCT name || '|' || shop_name || '|' || platform) as total_items,
                ROUND(100.0 * SUM(CASE WHEN is_available = true THEN 1 ELSE 0 END) / COUNT(*), 1) as availability_pct,
                SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) as offline_count")
            ->groupBy('category')->orderByRaw('CAST(category AS TEXT)')->get();

        return [
            'item_stats'       => [
                'total'            => (int) $totalItems,
                'offline'          => (int) $offlineItems,
                'online'           => (int) $onlineItems,
                'frequently_offline' => DB::table('items')->where('is_available', false)
                    ->where('updated_at', '>', \Carbon\Carbon::now('Asia/Singapore')->subDays(7))->count(),
            ],
            'top_offline_items'=> $topOfflineItems,
            'category_data'    => $categoryData,
        ];
    });

    return response()->json([
        'success'      => true,
        'item_stats'   => $reportData['item_stats'],
        'top_offline'  => $reportData['top_offline_items'],
        'categories'   => $reportData['category_data'],
        'total_stores' => DB::table('platform_status')->distinct()->count('shop_id'),
    ]);
});

// GET /api/reports/store-comparison
Route::get('/reports/store-comparison', function () {
    $shopMap = \App\Helpers\ShopHelper::getShopMap();

    $dbData = \Illuminate\Support\Facades\Cache::remember('store_comparison_db', 300, function () {
        $shopIds = DB::table('platform_status')->select('shop_id')->distinct()->pluck('shop_id')->toArray();
        return [
            'shopIds'             => $shopIds,
            'allPlatformStatuses' => DB::table('platform_status')->whereIn('shop_id', $shopIds)->get()
                ->groupBy('shop_id')->map(fn($i) => $i->keyBy('platform')),
            'itemCounts'          => DB::table('items')
                ->select('shop_name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN is_available = false THEN 1 ELSE 0 END) as offline'))
                ->groupBy('shop_name')->get()->keyBy('shop_name'),
        ];
    });

    $stores = collect($dbData['shopIds'])->map(function ($shopId) use ($shopMap, $dbData) {
        $ps           = $dbData['allPlatformStatuses']->get($shopId, collect());
        $onlinePlatforms = $ps->filter(fn($p) => $p->is_online)->count();
        $totalPlatforms  = $ps->count() ?: 3;
        $shopName     = $shopMap[$shopId]['name'] ?? 'Unknown Store';
        $itemData     = $dbData['itemCounts']->get($shopName, (object)['total' => 0, 'offline' => 0]);
        $totalItems   = $itemData->total ?? 0;
        $offlineItems = $itemData->offline ?? 0;
        $lastChecked  = $ps->max('last_checked_at');

        return [
            'shop_id'          => $shopId,
            'shop_name'        => $shopName,
            'overall_status'   => $onlinePlatforms === $totalPlatforms ? 'All Online'
                : ($onlinePlatforms === 0 ? 'All Offline' : 'Partial'),
            'platforms_online' => $onlinePlatforms,
            'total_platforms'  => $totalPlatforms,
            'grab_online'      => (bool)($ps->get('grab')?->is_online),
            'foodpanda_online' => (bool)($ps->get('foodpanda')?->is_online),
            'total_items'      => (int) $totalItems,
            'offline_items'    => (int) $offlineItems,
            'availability_pct' => $totalItems > 0 ? round((($totalItems - $offlineItems) / $totalItems) * 100, 1) : 0,
            'last_checked'     => $lastChecked,
            'last_checked_human' => $lastChecked ? \Carbon\Carbon::parse($lastChecked)->diffForHumans() : 'Never',
        ];
    })->sortBy('shop_name')->values();

    $summary = [
        'total'        => $stores->count(),
        'all_online'   => $stores->where('overall_status', 'All Online')->count(),
        'partial'      => $stores->where('overall_status', 'Partial')->count(),
        'all_offline'  => $stores->where('overall_status', 'All Offline')->count(),
        'total_items'  => $stores->sum('total_items'),
        'offline_items'=> $stores->sum('offline_items'),
    ];

    return response()->json(['success' => true, 'summary' => $summary, 'stores' => $stores]);
});

// GET /api/settings/configuration
Route::get('/settings/configuration', function () {
    $configs = \App\Models\Configuration::all()->keyBy('key');
    return response()->json([
        'success' => true,
        'data'    => [
            'scraper_run_interval'           => $configs->get('scraper_run_interval')?->value          ?? 'every_10_minutes',
            'auto_refresh_interval'          => $configs->get('auto_refresh_interval')?->value         ?? 'every_5_minutes',
            'enable_parallel_scraping'       => (bool)($configs->get('enable_parallel_scraping')?->value      ?? true),
            'enable_platform_offline_alerts' => (bool)($configs->get('enable_platform_offline_alerts')?->value ?? true),
            'enable_high_offline_items_alert'=> (bool)($configs->get('enable_high_offline_items_alert')?->value ?? true),
            'offline_items_threshold'        => $configs->get('offline_items_threshold')?->value       ?? '20',
            'alert_email'                    => $configs->get('alert_email')?->value                   ?? '',
            'timezone'                       => $configs->get('timezone')?->value                      ?? 'Asia/Singapore',
            'date_format'                    => $configs->get('date_format')?->value                   ?? 'DD/MM/YYYY',
            'show_item_images'               => (bool)($configs->get('show_item_images')?->value       ?? true),
        ],
    ]);
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
    $secret = env('ACTIONS_SECRET');
    if ($secret && !hash_equals($secret, (string) request()->header('X-Actions-Secret', ''))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    try {
        $apiKey    = env('RESEND_API_KEY');
        $fromEmail = env('ALERT_FROM_EMAIL', 'HawkerOps <onboarding@resend.dev>');

        // Check DB config first (Settings page), fall back to env var
        $dbEmail = DB::table('configurations')->where('key', 'alert_email')->value('value');
        $toRaw   = $dbEmail ?: env('ALERT_TO_EMAILS', '');
        $toEmails = array_values(array_filter(array_map('trim', explode(',', $toRaw))));

        if (!$apiKey || empty($toEmails)) {
            return response()->json(['success' => false, 'message' => 'Email alert not configured — set ALERT_TO_EMAILS in Render env vars or configure alert_email in Settings'], 400);
        }

        // ── Scraper failure check ─────────────────────────────────────────────
        (new \App\Services\AlertService())->checkScraperFailure();

        $nowSgt = \Carbon\Carbon::now('Asia/Singapore');

        // ── Change detection: only alert when NEW stores go offline ──────────
        // Get current set of offline shop_ids
        $currentOfflineIds = DB::table('platform_status')
            ->where('is_online', false)
            ->distinct()
            ->pluck('shop_id')
            ->sort()
            ->values()
            ->toArray();

        // All clear — reset cached state
        if (empty($currentOfflineIds)) {
            \Illuminate\Support\Facades\Cache::forget('hawkerops_alert_offline_ids');
            return response()->json(['success' => true, 'message' => 'All clear — no alert needed']);
        }

        // Compare to last alerted offline set
        $lastOfflineIds    = \Illuminate\Support\Facades\Cache::get('hawkerops_alert_offline_ids', []);
        $lastSentAt        = \Illuminate\Support\Facades\Cache::get('hawkerops_alert_last_sent');
        $minutesSinceLast  = $lastSentAt ? \Carbon\Carbon::parse($lastSentAt)->diffInMinutes(now()) : 9999;

        $newOfflineIds = array_values(array_diff($currentOfflineIds, $lastOfflineIds));
        $hasNewIssues  = !empty($newOfflineIds);

        // Send if new issues (15-min cooldown) OR 2-hour reminder for ongoing issues
        $isReminder = !$hasNewIssues && $minutesSinceLast >= 120;

        if (!$hasNewIssues && !$isReminder) {
            return response()->json(['success' => true, 'message' => 'No new issues — same stores still offline, skipping']);
        }

        // 15-min rate limit guard for rapid-fire new issues
        if ($hasNewIssues && $minutesSinceLast < 15) {
            return response()->json(['success' => false, 'message' => 'Rate limited', 'next_in_minutes' => 15 - $minutesSinceLast], 429);
        }
        // ─────────────────────────────────────────────────────────────────────

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

        // Menu items offline
        $itemsOffline = DB::table('items')
            ->where('is_available', false)
            ->whereIn('platform', ['grab', 'foodpanda'])
            ->count();

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
        $reportUrl = env('APP_URL', 'https://resto-v3-5.onrender.com') . '/history/' . $nowSgt->format('Y-m-d');
        $newCount  = count($newOfflineIds);
        $subject   = $hasNewIssues
            ? "⚠️ HawkerOps — {$newCount} new store" . ($newCount !== 1 ? 's' : '') . " went offline · {$dateStr}"
            : "🔔 HawkerOps — {$storesWithIssues} stores still offline (2h reminder) · {$dateStr}";

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
            \Illuminate\Support\Facades\Cache::put('hawkerops_alert_last_sent', now()->toIso8601String(), 7200);
            \Illuminate\Support\Facades\Cache::put('hawkerops_alert_offline_ids', $currentOfflineIds, 86400);
            return response()->json([
                'success'   => true,
                'message'   => 'Alert email sent',
                'type'      => $hasNewIssues ? 'new_issues' : 'reminder',
                'new_stores' => $newCount,
                'to'        => $toEmails,
                'subject'   => $subject,
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
