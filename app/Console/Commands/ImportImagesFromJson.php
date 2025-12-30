<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportImagesFromJson extends Command
{
    protected $signature = 'import:images-json
        {file : Path to JSON file with image data}
        {--shopId= : Optional shop ID to filter}';

    protected $description = 'Import item images from RestoSuite BO JSON export';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $shopId = $this->option('shopId');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Reading JSON from: {$filePath}");

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error("Invalid JSON file");
            return self::FAILURE;
        }

        $updated = 0;
        $notFound = 0;

        // Handle different JSON structures
        $items = $this->extractItems($data);

        $this->info("Found " . count($items) . " items in JSON");

        foreach ($items as $item) {
            $itemName = $item['itemName'] ?? $item['name'] ?? null;
            $imageUrl = $item['itemImage'] ?? $item['itemImageUrl'] ?? null;

            if (!$itemName || !$imageUrl) {
                continue;
            }

            // Try to find matching item in database
            $query = DB::table('restosuite_item_snapshots')
                ->where('name', $itemName);

            if ($shopId) {
                $query->where('shop_id', $shopId);
            }

            $count = $query->update([
                'image_url' => $imageUrl,
                'updated_at' => now(),
            ]);

            if ($count > 0) {
                $updated += $count;
                $this->line("  ✓ Updated: {$itemName}");
            } else {
                $notFound++;
                $this->warn("  ✗ Not found: {$itemName}");
            }
        }

        $this->info("Done! Updated {$updated} items, {$notFound} not found");

        return self::SUCCESS;
    }

    private function extractItems(array $data): array
    {
        $items = [];

        // Try different possible structures
        if (isset($data['data']['itemRelations'])) {
            // RestoSuite BO format
            $items = $data['data']['itemRelations'];
        } elseif (isset($data['items'])) {
            $items = $data['items'];
        } elseif (isset($data['data']['items'])) {
            $items = $data['data']['items'];
        } elseif (isset($data['itemBindingList'])) {
            $items = $data['itemBindingList'];
        } else {
            // Assume the data itself is an array of items
            $items = $data;
        }

        return is_array($items) ? $items : [];
    }
}
