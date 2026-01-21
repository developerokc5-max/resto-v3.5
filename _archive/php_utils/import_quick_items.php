<?php
require __DIR__ . '/vendor/autoload.php';
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Extract JSON from items_quick.json
$content = file_get_contents('items_quick.json');
$lines = explode("\n", $content);
$jsonStart = 0;
foreach ($lines as $i => $line) {
    if (trim($line) === '{') {
        $jsonStart = $i;
        break;
    }
}

$jsonContent = implode("\n", array_slice($lines, $jsonStart));
$data = json_decode($jsonContent, true);

if (!$data || !isset($data['stores'])) {
    die("ERROR: Could not parse JSON\n");
}

echo "Importing real scraped items with images...\n";
echo "Stores: " . count($data['stores']) . "\n";
echo "Total items: " . $data['total_items'] . "\n\n";

// Clear existing
DB::table('items')->truncate();

$imported = 0;
foreach ($data['stores'] as $storeName => $items) {
    echo "Importing from: $storeName\n";

    foreach ($items as $item) {
        // Insert for each platform
        foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
            DB::table('items')->insert([
                'item_id' => $item['sku'] ?: 'unknown',
                'shop_name' => $storeName,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'category' => $item['category'],
                'price' => $item['price'],
                'image_url' => $item['image_url'],
                'is_available' => $item['is_available'],
                'platform' => $platform,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $imported++;
        }
    }
}

echo "\n✓ Imported $imported item records (across 3 platforms)\n";
echo "✓ Ready at: http://localhost:8000/items\n";
