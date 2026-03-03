<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per scrape event per day.
     * Inserted by POST /api/history/snapshot after each automated scrape.
     * Used to show scrape count on history list and recovery timeline on detail page.
     */
    public function up(): void
    {
        Schema::create('daily_scrape_log', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->timestamp('scanned_at');
            $table->integer('stores_total')->default(0);
            $table->integer('stores_offline')->default(0);
            $table->integer('items_offline')->default(0);
            // JSON array of {shop_id, shop_name} that recovered this scrape (was offline, now online)
            $table->text('recoveries')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_scrape_log');
    }
};
