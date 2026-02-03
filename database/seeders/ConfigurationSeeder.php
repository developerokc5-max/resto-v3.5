<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing configurations
        DB::table('configurations')->truncate();

        // Scraper Schedule Settings
        DB::table('configurations')->insert([
            'key' => 'scraper_run_interval',
            'value' => 'every_10_minutes',
            'type' => 'string',
            'description' => 'How often scrapers run to fetch latest data',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'auto_refresh_interval',
            'value' => 'every_5_minutes',
            'type' => 'string',
            'description' => 'How often pages auto-reload to show fresh data',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'enable_parallel_scraping',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Run all 3 scrapers simultaneously for faster updates',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Alert & Notification Settings
        DB::table('configurations')->insert([
            'key' => 'enable_platform_offline_alerts',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Get notified when a platform goes offline',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'enable_high_offline_items_alert',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Alert when offline items exceed threshold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'offline_items_threshold',
            'value' => '20',
            'type' => 'number',
            'description' => 'Alert when offline items exceed this number',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'alert_email',
            'value' => 'alerts@example.com',
            'type' => 'email',
            'description' => 'Where to send critical alerts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Display Settings
        DB::table('configurations')->insert([
            'key' => 'timezone',
            'value' => 'Asia/Singapore',
            'type' => 'string',
            'description' => 'Application timezone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'date_format',
            'value' => 'DD/MM/YYYY',
            'type' => 'string',
            'description' => 'Date format for display',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('configurations')->insert([
            'key' => 'show_item_images',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Display product images in item lists',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
