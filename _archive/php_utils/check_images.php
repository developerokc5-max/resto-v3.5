<?php

require __DIR__ . '/vendor/autoload.php';
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = DB::table('items')->count();
$withImages = DB::table('items')->whereNotNull('image_url')->where('image_url', '!=', '')->count();

echo "Total items: $total\n";
echo "Items with images: $withImages\n";

$sample = DB::table('items')->whereNotNull('image_url')->where('image_url', '!=', '')->first();
if ($sample) {
    echo "\nSample item with image:\n";
    echo "  Name: {$sample->name}\n";
    echo "  Price: \${$sample->price}\n";
    echo "  Shop: {$sample->shop_name}\n";
    echo "  Image URL: " . substr($sample->image_url, 0, 100) . "...\n";
} else {
    echo "\nNo items with images found.\n";
}
