<?php

$items = json_decode(file_get_contents('scraped_items_final.json'), true);

$emptyNames = 0;
$validNames = 0;

foreach ($items as $item) {
    if (empty($item['item_name'])) {
        $emptyNames++;
    } else {
        $validNames++;
    }
}

echo "Items with empty names: $emptyNames\n";
echo "Items with valid names: $validNames\n";
echo "Total items: " . count($items) . "\n\n";

// Show a few items with valid names
echo "Sample items with valid names:\n";
$count = 0;
foreach ($items as $item) {
    if (!empty($item['item_name']) && $count < 3) {
        echo "\nItem:\n";
        echo "  store: {$item['store_name']}\n";
        echo "  name: {$item['item_name']}\n";
        echo "  price: {$item['price']}\n";
        echo "  platform: {$item['platform']}\n";
        echo "  image: " . substr($item['image_url'] ?? 'NO IMAGE', 0, 60) . "\n";
        $count++;
    }
}
