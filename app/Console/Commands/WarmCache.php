<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\CacheOptimizationHelper;
use App\Helpers\ShopHelper;

/**
 * WarmCache — pre-populates all expensive caches before traffic hits.
 *
 * Run automatically at the end of render-build.sh so every Render deploy
 * starts with warm caches. Without this, the first visitor after each deploy
 * triggers all DB queries cold, causing 6-8s page loads.
 */
class WarmCache extends Command
{
    protected $signature   = 'cache:warm';
    protected $description = 'Pre-warm all application caches to eliminate cold-start DB queries after deploy';

    public function handle(): int
    {
        $this->info('⚡ Warming application caches...');
        $start = microtime(true);

        try {
            // ── Shared base data ─────────────────────────────────────────────
            $this->line('  → shop_map');
            $shopMap = ShopHelper::getShopMap();

            // ── Dashboard caches ─────────────────────────────────────────────
            $this->line('  → dashboard_kpis_consolidated');
            CacheOptimizationHelper::getDashboardKPIs();

            $this->line('  → store_stats_consolidated');
            CacheOptimizationHelper::getConsolidatedStoreStats();

            $this->line('  → offline_items_by_shop_platform');
            CacheOptimizationHelper::getOfflineItemsPerShopPlatform();

            $this->line('  → all_platform_statuses');
            CacheOptimizationHelper::getAllPlatformStatuses();

            $this->line('  → recent_changes_per_shop (1d + 7d)');
            CacheOptimizationHelper::getRecentChangesPerShop(1);
            CacheOptimizationHelper::getRecentChangesPerShop(7);

            $this->line('  → alert_metrics_consolidated');
            CacheOptimizationHelper::getAlertMetrics();

            // ── Stores page cache ─────────────────────────────────────────────
            $this->line('  → stores_page_data');
            Cache::remember('stores_page_data', 300, function () use ($shopMap) {
                $allShops = DB::table('shops')->orderBy('shop_name')->get();

                $allItemCounts = DB::table('items')
                    ->select('shop_name', DB::raw("COUNT(DISTINCT (name || '|' || category)) as total_count"))
                    ->groupBy('shop_name')
                    ->pluck('total_count', 'shop_name');

                $allOfflineCounts = DB::table('items')
                    ->select('shop_name', DB::raw("COUNT(DISTINCT (name || '|' || category)) as offline_count"))
                    ->where('is_available', false)
                    ->groupBy('shop_name')
                    ->pluck('offline_count', 'shop_name');

                $allPlatformStatuses = DB::table('platform_status')
                    ->get()
                    ->groupBy('store_name');

                $stores = [];
                foreach ($allShops as $shop) {
                    $totalUniqueItems = $allItemCounts[$shop->shop_name] ?? 0;
                    $itemsOffCount    = $allOfflineCounts[$shop->shop_name] ?? 0;

                    $platformStatus = collect($allPlatformStatuses[$shop->shop_name] ?? [])
                        ->keyBy('platform');

                    $onlineCount  = 0;
                    $offlineCount = 0;
                    foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                        if ($platformStatus->has($platform)) {
                            if ($platformStatus->get($platform)->is_online) {
                                $onlineCount++;
                            } else {
                                $offlineCount++;
                            }
                        }
                    }

                    if ($onlineCount === 3) {
                        $status     = 'all_online';
                        $statusText = 'All Platforms Online';
                    } elseif ($onlineCount === 0) {
                        $status     = 'all_offline';
                        $statusText = 'All Platforms Offline';
                    } else {
                        $status     = 'partial_offline';
                        $statusText = "{$offlineCount}/3 Offline";
                    }

                    $shopInfo = $shopMap[$shop->shop_id] ?? [
                        'name'  => $shop->shop_name,
                        'brand' => $shop->organization_name ?? $shop->shop_name ?? 'Unknown',
                    ];

                    $stores[] = [
                        'brand'             => $shopInfo['brand'],
                        'store'             => $shopInfo['name'],
                        'shop_id'           => $shop->shop_id,
                        'status'            => $status,
                        'status_text'       => $statusText,
                        'platforms_online'  => $onlineCount,
                        'platforms_offline' => $offlineCount,
                        'total_items'       => (int) ($totalUniqueItems ?? 0),
                        'items_off'         => (int) ($itemsOffCount ?? 0),
                        'alerts'            => 0,
                        'last_change'       => $shop->last_synced_at
                            ? \Carbon\Carbon::parse($shop->last_synced_at)->diffForHumans()
                            : '—',
                    ];
                }

                return $stores;
            });

            // ── Platforms page cache ──────────────────────────────────────────
            $this->line('  → platforms_page_data');
            Cache::remember('platforms_page_data', 300, function () use ($shopMap) {
                $testingShopIds = [];
                foreach ($shopMap as $shopId => $info) {
                    if (stripos($info['name'], 'testing') !== false ||
                        stripos($info['name'], 'edge') !== false ||
                        stripos($info['name'], 'depot') !== false) {
                        $testingShopIds[] = $shopId;
                    }
                }

                $platformStatuses = DB::table('platform_status')
                    ->whereNotIn('shop_id', $testingShopIds)
                    ->orderBy('shop_id')->orderBy('platform')
                    ->get();

                $shopsPlatforms = [];
                foreach ($platformStatuses as $status) {
                    if (!isset($shopsPlatforms[$status->shop_id])) {
                        $fallbackName = $status->store_name && $status->store_name !== 'Unknown'
                            ? $status->store_name : $status->shop_id;
                        $shopInfo = $shopMap[$status->shop_id] ?? ['name' => $fallbackName, 'brand' => $fallbackName];
                        $shopsPlatforms[$status->shop_id] = [
                            'shop_id'   => $status->shop_id,
                            'shop_name' => $shopInfo['name'],
                            'brand'     => $shopInfo['brand'],
                            'platforms' => [],
                        ];
                    }
                    $shopsPlatforms[$status->shop_id]['platforms'][$status->platform] = [
                        'is_online'    => (bool) $status->is_online,
                        'items_synced' => $status->items_synced ?? 0,
                        'items_total'  => $status->items_total ?? 0,
                        'last_checked' => $status->last_checked_at
                            ? \Carbon\Carbon::parse($status->last_checked_at)->diffForHumans()
                            : 'Never',
                        'status' => $status->last_check_status ?? 'unknown',
                    ];
                }

                $totalPlatforms   = $platformStatuses->count();
                $onlinePlatforms  = $platformStatuses->where('is_online', true)->count();
                $offlinePlatforms = $totalPlatforms - $onlinePlatforms;

                $stats = [
                    'total'      => $totalPlatforms,
                    'online'     => $onlinePlatforms,
                    'offline'    => $offlinePlatforms,
                    'percentage' => $totalPlatforms > 0
                        ? round(($onlinePlatforms / $totalPlatforms) * 100, 2) : 0,
                ];

                $platformStats = [];
                foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
                    $pd     = $platformStatuses->where('platform', $platform);
                    $total  = $pd->count();
                    $online = $pd->where('is_online', true)->count();
                    $platformStats[$platform] = [
                        'total'      => $total,
                        'online'     => $online,
                        'offline'    => $total - $online,
                        'percentage' => $total > 0 ? round(($online / $total) * 100, 2) : 0,
                    ];
                }

                return [$shopsPlatforms, $stats, $platformStats];
            });

            // ── Items page support caches ─────────────────────────────────────
            $this->line('  → shops_name_list');
            Cache::remember('shops_name_list', 3600, function () {
                return DB::table('shops')
                    ->select('shop_name')->orderBy('shop_name')
                    ->pluck('shop_name')->values();
            });

            $this->line('  → items_categories');
            Cache::remember('items_categories', 300, function () {
                return DB::table('items')
                    ->select('category')->distinct()
                    ->whereNotNull('category')
                    ->pluck('category')->sort()->values();
            });

            // ── Misc ──────────────────────────────────────────────────────────
            $this->line('  → last_sync_unix');
            Cache::remember('last_sync_unix', 60, function () {
                $candidates = array_filter([
                    DB::table('platform_status')->max('last_checked_at'),
                    DB::table('restosuite_item_snapshots')->max('updated_at'),
                ]);
                if (empty($candidates)) return 0;
                return \Carbon\Carbon::parse(max($candidates))->timestamp;
            });

            $elapsed = round((microtime(true) - $start) * 1000);
            $this->info("✅ Cache warm-up complete in {$elapsed}ms");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Cache warm-up failed: ' . $e->getMessage());
            // Don't fail the deploy — server can still warm caches on first request
            return Command::SUCCESS;
        }
    }
}
