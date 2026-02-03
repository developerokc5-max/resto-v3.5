<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScraperLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing logs
        DB::table('scraper_logs')->truncate();

        // Platform Status Scraper - Feb 3, 2026 @ 10:01 AM
        DB::table('scraper_logs')->insert([
            'scraper_name' => 'platform',
            'status' => 'success',
            'items_processed' => 138,
            'items_updated' => 138,
            'log_message' => 'Scanned 46 stores across 3 platforms (Grab, Deliveroo, FoodPanda). Saved 138 platform status records.',
            'executed_at' => Carbon::parse('2026-02-03 10:01:17', 'Asia/Singapore'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Items Scraper - Feb 3, 2026 @ 10:42 AM
        DB::table('scraper_logs')->insert([
            'scraper_name' => 'items',
            'status' => 'success',
            'items_processed' => 7455,
            'items_updated' => 7455,
            'log_message' => 'Processed 46 outlets with parallel workers. Collected 7,455 items across 3 platforms (Grab, FoodPanda, Deliveroo). Execution time: 43.1 minutes.',
            'executed_at' => Carbon::parse('2026-02-03 10:42:40', 'Asia/Singapore'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Previous run example (yesterday)
        DB::table('scraper_logs')->insert([
            'scraper_name' => 'platform',
            'status' => 'success',
            'items_processed' => 138,
            'items_updated' => 135,
            'log_message' => 'Routine platform status check completed.',
            'executed_at' => Carbon::parse('2026-02-02 10:00:00', 'Asia/Singapore'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('scraper_logs')->insert([
            'scraper_name' => 'items',
            'status' => 'success',
            'items_processed' => 7200,
            'items_updated' => 7150,
            'log_message' => 'Daily item synchronization completed successfully.',
            'executed_at' => Carbon::parse('2026-02-02 11:00:00', 'Asia/Singapore'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
