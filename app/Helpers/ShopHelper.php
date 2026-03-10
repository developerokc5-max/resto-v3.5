<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopHelper
{
    /**
     * Build shop map dynamically from both sources:
     * - platform_status: numeric shop_id → store_name (used by platform scraper)
     * - shops: outlet-name shop_id → shop_name + brand (used by item scraper)
     * Cached for 5 minutes to avoid repeated DB queries.
     */
    public static function getShopMap(): array
    {
        return Cache::remember('shop_map', 300, function () {
            // Source 1: platform_status table — numeric shop IDs → store_name
            $platformMap = DB::table('platform_status')
                ->select('shop_id', 'store_name')
                ->whereNotNull('store_name')
                ->groupBy('shop_id', 'store_name')
                ->get()
                ->keyBy('shop_id')
                ->map(function ($row) {
                    $name  = $row->store_name;
                    $brand = str_contains($name, ' @ ') ? trim(explode(' @ ', $name)[0]) : $name;
                    return ['name' => $name, 'brand' => $brand];
                })
                ->toArray();

            // Source 2: shops table — outlet-name shop IDs → shop_name + organization_name
            $shopsMap = DB::table('shops')
                ->get()
                ->keyBy('shop_id')
                ->map(fn($shop) => [
                    'name'  => $shop->shop_name,
                    'brand' => $shop->organization_name ?? $shop->shop_name,
                ])
                ->toArray();

            // Merge: shopsMap takes priority (has full brand info)
            return array_merge($platformMap, $shopsMap);
        });
    }
}
