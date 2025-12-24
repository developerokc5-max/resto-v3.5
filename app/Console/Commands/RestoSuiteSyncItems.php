<?php

namespace App\Console\Commands;

use App\Services\RestoSuite\RestoSuiteClient;
use App\Services\RestoSuite\RestoSuiteException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestoSuiteSyncItems extends Command
{
    protected $signature = 'restosuite:sync-items
        {--page=1 : Items page number (if API paginates)}
        {--size=100 : Items page size}
        {--shopId= : Optional single shopId to sync only one shop}
        {--dry-run : Don\'t write DB, just show counts}';

    protected $description = 'Sync items snapshot for all shops and log changes';

    public function handle(RestoSuiteClient $client): int
    {
        $page = max(1, (int) $this->option('page'));
        $size = max(1, min(200, (int) $this->option('size')));
        $onlyShopId = trim((string) ($this->option('shopId') ?? ''));
        $dryRun = (bool) $this->option('dry-run');

        $runId = now()->toDateTimeString();

        try {
            $this->info("Starting sync | page={$page} | size={$size}" . ($dryRun ? " | DRY-RUN" : ""));

            // 1) Get shops
            $shops = $client->getShops(1, 500);
            if ($onlyShopId !== '') {
                $shops = array_values(array_filter($shops, fn ($s) => (string)($s['shopId'] ?? '') === $onlyShopId));
            }

            $this->info('Shops to sync: ' . count($shops));
            if (count($shops) === 0) {
                $this->warn('No shops matched. Done.');
                return self::SUCCESS;
            }

            $totalItems = 0;
            $snapshotsInserted = 0;
            $changesInserted = 0;
            $skippedSame = 0;

            foreach ($shops as $shop) {
                $shopId = (string) ($shop['shopId'] ?? '');
                $shopName = (string) ($shop['name'] ?? '');
                if ($shopId === '') continue;

                $this->line("→ Shop {$shopId} | {$shopName}");

                // 2) Pull items from API
                $items = $client->getItems($shopId, $page, $size);
                $count = count($items);
                $totalItems += $count;

                $this->line("   Items fetched: {$count}");
                if ($count === 0) continue;

                // 3) Load latest snapshot per item for this shop (ONE query)
                //    Get the latest row id per (shop_id, item_id)
                $latestIds = DB::table('restosuite_item_snapshots')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('shop_id', $shopId)
                    ->groupBy('shop_id', 'item_id')
                    ->pluck('id')
                    ->toArray();

                $prevMap = [];
                if (!empty($latestIds)) {
                    $prevRows = DB::table('restosuite_item_snapshots')
                        ->whereIn('id', $latestIds)
                        ->get();

                    foreach ($prevRows as $r) {
                        $prevMap[(string)$r->item_id] = $r;
                    }
                }

                // 4) Process each item
                foreach ($items as $it) {
                    $itemUid = (string) ($it['itemUID'] ?? '');
                    if ($itemUid === '') {
                        continue;
                    }

                    $name = (string) ($it['itemName'] ?? $it['name'] ?? '');
                    $isActive = (int) ($it['isActive'] ?? 0);

                    // price: prefer size[0].basePrice, else basePrice, else ''
                    $price = '';
                    if (!empty($it['size'][0]['basePrice'])) {
                        $price = (string) $it['size'][0]['basePrice'];
                    } elseif (isset($it['basePrice']) && $it['basePrice'] !== '') {
                        $price = (string) $it['basePrice'];
                    }

                    // Compute "fingerprint" in PHP (no DB column needed)
                    $currentFp = $this->fp($shopId, $itemUid, $name, $isActive, $price);

                    $prev = $prevMap[$itemUid] ?? null;
                    $prevFp = null;

                    if ($prev) {
                        $prevFp = $this->fp(
                            (string) $prev->shop_id,
                            (string) $prev->item_id,
                            (string) ($prev->name ?? ''),
                            (int) ($prev->is_active ?? 0),
                            (string) ($prev->price ?? '')
                        );
                    }

                    // SAME -> SKIP
                    if ($prev && $prevFp === $currentFp) {
                        $skippedSame++;
                        continue;
                    }

                    // Build change payload
                    $change = [];
                    if (!$prev) {
                        $change['created'] = true;
                    } else {
                        if ((string)($prev->name ?? '') !== $name) {
                            $change['name'] = ['from' => (string)($prev->name ?? ''), 'to' => $name];
                        }
                        if ((int)($prev->is_active ?? 0) !== $isActive) {
                            $change['is_active'] = ['from' => (int)($prev->is_active ?? 0), 'to' => $isActive];
                        }
                        if ((string)($prev->price ?? '') !== (string)$price) {
                            $change['price'] = ['from' => (string)($prev->price ?? ''), 'to' => (string)$price];
                        }
                    }

                    if ($dryRun) {
                        $snapshotsInserted++; // "would insert"
                        if (!empty($change)) $changesInserted++;
                        continue;
                    }

                    // INSERT snapshot
                    DB::table('restosuite_item_snapshots')->insert([
                        'run_id'     => $runId,
                        'shop_id'    => $shopId,
                        'item_id'    => $itemUid,
                        'item_uid'   => $itemUid,
                        'name'       => $name,
                        'is_active'  => $isActive,
                        'price'      => $price,
                        'raw_json'   => json_encode($it, JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $snapshotsInserted++;

                    // INSERT change row only if there is something to log
                    if (!empty($change)) {
                        DB::table('restosuite_item_changes')->insert([
                            'run_id'      => $runId,
                            'shop_id'     => $shopId,
                            'item_id'     => $itemUid,
                            'item_uid'    => $itemUid,
                            'change_json' => json_encode($change, JSON_UNESCAPED_UNICODE),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        $changesInserted++;
                    }
                }
            }

            $this->info("Done.");
            $this->info("Total items fetched: {$totalItems}");
            if ($dryRun) {
                $this->info("Would insert snapshots: {$snapshotsInserted} | Would insert changes: {$changesInserted} | Would skip (same): {$skippedSame}");
            } else {
                $this->info("Snapshots inserted: {$snapshotsInserted} | Changes inserted: {$changesInserted} | Skipped (same): {$skippedSame}");
            }

            return self::SUCCESS;

        } catch (RestoSuiteException $e) {
            $this->error("RestoSuiteException: {$e->getMessage()}");
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function fp(string $shopId, string $itemId, string $name, int $isActive, string $price): string
    {
        // normalize whitespace + case for stability
        $name = trim(preg_replace('/\s+/', ' ', $name ?? ''));
        $price = trim((string)$price);

        return sha1($shopId . '|' . $itemId . '|' . $name . '|' . $isActive . '|' . $price);
    }
}
