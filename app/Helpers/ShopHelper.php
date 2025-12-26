<?php

namespace App\Helpers;

use App\Services\RestoSuite\RestoSuiteClient;

class ShopHelper
{
    private static $shopMap = null;

    public static function getShopMap(): array
    {
        if (self::$shopMap === null) {
            $client = app(RestoSuiteClient::class);
            $shopsData = $client->getShops(1, 500);
            self::$shopMap = [];
            foreach ($shopsData as $shop) {
                self::$shopMap[$shop['shopId']] = [
                    'name' => $shop['name'],
                    'brand' => $shop['brandName'],
                ];
            }
        }
        return self::$shopMap;
    }
}
