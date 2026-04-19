<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopHelper
{
    /**
     * Numeric shop IDs to exclude — used to filter platform_status table.
     */
    public static function excludedShopIds(): array
    {
        return [
            '401525442', // OKCR Testing Outlet
            '404055818', // HUMFULL Testing Outlet
            '402214336', // JKT Western Testing Outlet
            '405576685', // Le Le Mee Pok Testing Outlet
            '404144535', // Drinks Stall Testing Outlet
            '408443497', // AH HUAT HOKKIEN MEE (Demo outlet)
            '402473827', // OK CHICKEN RICE @ AMK (closed)
            '409789948', // OK CHICKEN RICE @ Depot (closed)
            '407006583', // HUMFULL @ Edgedale Plains (closed)
        ];
    }

    /**
     * Shop display names to exclude — used to filter shops / items tables.
     * Must stay in sync with excludedShopIds() above.
     */
    public static function excludedShopNames(): array
    {
        return [
            'OKCR Testing Outlet',
            'HUMFULL Testing Outlet',
            'JKT Western Testing Outlet',
            'Le Le Mee Pok Testing Outlet',
            'Drinks Stall Testing Outlet',
            'AH HUAT HOKKIEN MEE ( Demo outlet )',
            'OK CHICKEN RICE @ AMK',
            'OK CHICKEN RICE @ Depot',
            'HUMFULL @ Edgedale Plains',
        ];
    }

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

            // Source 2: shops table — outlet-name shop IDs → shop_name + brand column
            $shopsMap = DB::table('shops')
                ->get()
                ->keyBy('shop_id')
                ->map(fn($shop) => [
                    'name'  => $shop->shop_name,
                    'brand' => $shop->brand ?? $shop->organization_name ?? $shop->shop_name,
                ])
                ->toArray();

            // Merge: shopsMap takes priority (has full brand info)
            // Use + operator to preserve integer keys (array_merge renumbers them)
            return $shopsMap + $platformMap;
        });
    }
}
