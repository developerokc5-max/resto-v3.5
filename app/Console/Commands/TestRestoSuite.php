<?php

namespace App\Console\Commands;

use App\Services\RestoSuite\RestoSuiteClient;
use App\Services\RestoSuite\RestoSuiteException;
use Illuminate\Console\Command;
use Throwable;

class TestRestoSuite extends Command
{
    /**
     * One command that can test shops OR items.
     */
    protected $signature = 'restosuite:test
        {--type=shops : shops|items}
        {--shopId= : required when type=items}
        {--page=1 : page number}
        {--size=50 : page size (max 200)}';

    protected $description = 'Test RestoSuite API (shops or items)';

    public function handle(RestoSuiteClient $client): int
    {
        $type = strtolower((string) $this->option('type'));
        $page = max(1, (int) $this->option('page'));
        $size = max(1, min(200, (int) $this->option('size')));
        $shopId = trim((string) $this->option('shopId'));

        try {
            if ($type === 'items') {
                if ($shopId === '') {
                    $this->error('Missing --shopId. Example: php artisan restosuite:test --type=items --shopId=402473827');
                    return self::FAILURE;
                }

                $this->info("Fetching ITEM list | shopId={$shopId} | page={$page} | size={$size}");

                // Expecting client->getItems() to return the LIST array (not the whole JSON)
                $items = $client->getItems($shopId, $page, $size);

                $this->info('Items count: ' . count($items));

                $rows = array_map(fn ($i) => [
                    $i['itemId'] ?? ($i['id'] ?? ''),
                    $i['name'] ?? ($i['itemName'] ?? ''),
                    $i['status'] ?? ($i['saleStatus'] ?? ''),
                    $i['price'] ?? ($i['salePrice'] ?? ''),
                ], array_slice($items, 0, 30));

                $this->table(['itemId', 'name', 'status', 'price'], $rows);

                return self::SUCCESS;
            }

            // Default: shops
            if ($type !== 'shops') {
                $this->warn("Unknown --type={$type}. Using shops.");
            }

            $this->info("Fetching SHOP list | page={$page} | size={$size}");

            // Expecting client->getShops() to return the LIST array (not the whole JSON)
            $shops = $client->getShops($page, $size);

            $this->info('Shops count: ' . count($shops));

            $rows = array_map(fn ($s) => [
                $s['shopId'] ?? '',
                $s['name'] ?? '',
                $s['brandName'] ?? '',
                $s['orgCode'] ?? '',
                $s['storeStatus'] ?? ($s['operatingStatus'] ?? ''),
            ], $shops);

            $this->table(['shopId', 'name', 'brandName', 'orgCode', 'status'], $rows);

            $this->line('');
            $this->info('Items example: php artisan restosuite:test --type=items --shopId=402473827 --page=1 --size=50');

            return self::SUCCESS;

        } catch (RestoSuiteException $e) {
            $this->error('RestoSuiteException: ' . $e->getMessage());

            if (method_exists($e, 'context')) {
                $ctx = $e->context();
                if (!empty($ctx)) {
                    $this->line('Context: ' . json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
