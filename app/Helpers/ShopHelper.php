<?php

namespace App\Helpers;

class ShopHelper
{
    /**
     * Return hardcoded shop map to avoid API rate limits
     * This data comes from web scraping, not the RestoSuite API
     */
    public static function getShopMap(): array
    {
        return [
            400133646 => ['name' => 'HUMFULL @ Taman Jurong', 'brand' => 'HUMFULL'],
            400581144 => ['name' => 'HUMFULL @ Eunos', 'brand' => 'HUMFULL'],
            400996942 => ['name' => 'HUMFULL @ Bedok', 'brand' => 'HUMFULL'],
            401525442 => ['name' => 'OKCR Testing Outlet', 'brand' => 'OK CHICKEN RICE'],
            401585259 => ['name' => 'OK CHICKEN RICE @ Tampines', 'brand' => 'OK CHICKEN RICE'],
            401797825 => ['name' => 'OK CHICKEN RICE @ Taman Jurong', 'brand' => 'OK CHICKEN RICE'],
            401805288 => ['name' => 'HUMFULL @ Havelock', 'brand' => 'HUMFULL'],
            401974974 => ['name' => 'OK CHICKEN RICE @ Woodlands Height', 'brand' => 'OK CHICKEN RICE'],
            402214336 => ['name' => 'JKT Western Testing Outlet', 'brand' => 'JKT Western'],
            402473827 => ['name' => 'OK CHICKEN RICE @ AMK', 'brand' => 'OK CHICKEN RICE'],
            402951243 => ['name' => 'HUMFULL @ Toa Payoh', 'brand' => 'HUMFULL'],
            402969676 => ['name' => 'HUMFULL @ Teck Whye', 'brand' => 'HUMFULL'],
            403006377 => ['name' => 'HUMFULL @ Tampines Mart', 'brand' => 'HUMFULL'],
            403200723 => ['name' => 'HUMFULL @ Hougang', 'brand' => 'HUMFULL'],
            403216044 => ['name' => 'HUMFULL @ Yishun', 'brand' => 'HUMFULL'],
            403435788 => ['name' => 'OK CHICKEN RICE @ Bukit Batok', 'brand' => 'OK CHICKEN RICE'],
            403772840 => ['name' => 'OK CHICKEN RICE @ Lengkok Bahru', 'brand' => 'OK CHICKEN RICE'],
            403792341 => ['name' => 'HUMFULL @ Jurong East', 'brand' => 'HUMFULL'],
            403805056 => ['name' => 'OK CHICKEN RICE @ Jurong East', 'brand' => 'OK CHICKEN RICE'],
            403901255 => ['name' => 'HUMFULL @ Punggol', 'brand' => 'HUMFULL'],
            403988292 => ['name' => 'AH HUAT HOKKIEN MEE @ Bukit Batok', 'brand' => 'AH HUAT HOKKIEN MEE'],
            404055818 => ['name' => 'HUMFULL Testing Outlet', 'brand' => 'HUMFULL'],
            404131238 => ['name' => 'OK CHICKEN RICE @ Teck Whye', 'brand' => 'OK CHICKEN RICE'],
            404144535 => ['name' => 'Drinks Stall Testing Outlet', 'brand' => 'Drinks Stall'],
            405052838 => ['name' => '51 Toa Payoh Drinks', 'brand' => '51 Toa Payoh Drinks'],
            405521977 => ['name' => 'AH HUAT HOKKIEN MEE @ TPY', 'brand' => 'AH HUAT HOKKIEN MEE'],
            405576685 => ['name' => 'Le Le Mee Pok Testing Outlet', 'brand' => 'Le Le Mee Pok'],
            405591024 => ['name' => 'AH HUAT HOKKIEN MEE @ PUNGGOL', 'brand' => 'AH HUAT HOKKIEN MEE'],
            406095021 => ['name' => 'OK CHICKEN RICE @ Marsiling', 'brand' => 'OK CHICKEN RICE'],
            406125857 => ['name' => 'OK CHICKEN RICE @ Punggol', 'brand' => 'OK CHICKEN RICE'],
            406921266 => ['name' => 'HUMFULL @ Woodlands Height', 'brand' => 'HUMFULL'],
            407006583 => ['name' => 'HUMFULL @ Edgedale Plains', 'brand' => 'HUMFULL'],
            407290387 => ['name' => 'OK CHICKEN RICE @ Eunos', 'brand' => 'OK CHICKEN RICE'],
            407323028 => ['name' => 'JKT Western @ Toa Payoh', 'brand' => 'JKT Western'],
            407536268 => ['name' => 'HUMFULL @ Marsiling', 'brand' => 'HUMFULL'],
            407556803 => ['name' => 'OK CHICKEN RICE @ Hougang', 'brand' => 'OK CHICKEN RICE'],
            408078732 => ['name' => 'OK CHICKEN RICE @ Bedok', 'brand' => 'OK CHICKEN RICE'],
            408443497 => ['name' => 'AH HUAT HOKKIEN PRAWN MEE ( OFFICE TESTING OUTLET )', 'brand' => 'AH HUAT HOKKIEN MEE'],
            408543917 => ['name' => 'HUMFULL @ AMK', 'brand' => 'HUMFULL'],
            408759190 => ['name' => 'Le Le Mee Pok @ Toa Payoh', 'brand' => 'Le Le Mee Pok'],
            408971550 => ['name' => 'OK CHICKEN RICE @ Havelock', 'brand' => 'OK CHICKEN RICE'],
            409042218 => ['name' => 'HUMFULL @ Bukit Batok', 'brand' => 'HUMFULL'],
            409048465 => ['name' => 'OK CHICKEN RICE @ Yishun', 'brand' => 'OK CHICKEN RICE'],
            409111567 => ['name' => 'HUMFULL @ Lengkok Bahru', 'brand' => 'HUMFULL'],
            409618450 => ['name' => 'OK CHICKEN RICE @ Toa Payoh', 'brand' => 'OK CHICKEN RICE'],
            409789948 => ['name' => 'OK CHICKEN RICE @ Depot', 'brand' => 'OK CHICKEN RICE'],
        ];
    }
}
