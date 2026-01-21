<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shops = DB::table('platform_status')
    ->select('shop_id', 'store_name')
    ->distinct()
    ->orderBy('shop_id')
    ->get();

echo "Total shops: " . $shops->count() . "\n\n";
echo "Shop Map:\n";

foreach ($shops as $shop) {
    echo sprintf(
        "    %d => ['name' => '%s', 'brand' => '%s'],\n",
        $shop->shop_id,
        addslashes($shop->store_name),
        addslashes($shop->store_name)
    );
}
