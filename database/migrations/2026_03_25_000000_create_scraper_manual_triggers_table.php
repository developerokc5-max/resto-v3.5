<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS scraper_manual_triggers (
            id SERIAL PRIMARY KEY,
            scraper_type VARCHAR(20) NOT NULL,
            triggered_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS scraper_manual_triggers');
    }
};
