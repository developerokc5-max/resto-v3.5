<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});


Route::get('/dashboard', function () {
    // TEMP demo data (replace with DB later)
    $kpis = [
        'stores_online' => 44,
        'items_off'     => 0,
        'addons_off'    => 0,
        'alerts'        => 0,
    ];

    $stores = [
        [
            'brand' => 'OK Chicken Rice',
            'store' => 'AMK',
            'shop_id' => '402473827',
            'status' => 'OPERATING',
            'items_off' => 0,
            'addons_off' => 0,
            'alerts' => 0,
            'last_change' => '—',
        ],
    ];

    return view('dashboard', [
        'kpis' => $kpis,
        'stores' => $stores,
        'lastSync' => now()->format('h:i A'),
    ]);
});
