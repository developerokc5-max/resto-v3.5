<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopHelper
{
    /**
     * Build shop map dynamically from the shops table.
     * Cached for 5 minutes to avoid repeated DB queries.
     * New brands and outlets appear automatically — no hardcoding needed.
     */
    public static function getShopMap(): array
    {
        return Cache::remember('shop_map', 300, function () {
            return DB::table('shops')
                ->get()
                ->keyBy('shop_id')
                ->map(fn($shop) => [
                    'name'  => $shop->shop_name,
                    'brand' => $shop->organization_name ?? $shop->shop_name,
                ])
                ->toArray();
        });
    }
}
