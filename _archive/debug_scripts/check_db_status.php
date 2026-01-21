<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$total = DB::table('items')->count();
$withImages = DB::table('items')->whereNotNull('image_url')->where('image_url', '!=', '')->count();
$available = DB::table('items')->where('is_available', 1)->count();
$restaurants = DB::table('items')->distinct('shop_name')->count('shop_name');

echo "======================================\n";
echo "DATABASE STATUS\n";
echo "======================================\n";
echo "Total items: {$total}\n";
echo "Items with images: {$withImages}\n";
echo "Available items: {$available}\n";
echo "Unique restaurants: {$restaurants}\n";
echo "======================================\n";

if ($total > 0) {
    // Show sample item with image
    $sample = DB::table('items')->whereNotNull('image_url')->first();
    if ($sample) {
        echo "\nSample item:\n";
        echo "  Name: {$sample->name}\n";
        echo "  Shop: {$sample->shop_name}\n";
        echo "  Price: \${$sample->price}\n";
        echo "  Image: " . substr($sample->image_url, 0, 80) . "...\n";
    }
}
