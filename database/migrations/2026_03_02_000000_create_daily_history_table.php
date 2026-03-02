<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the daily_history table.
     *
     * One row per store per day (SGT date).
     * On each page visit / sync, today's rows are deleted + re-inserted
     * so the snapshot always reflects the latest state for that day.
     * Past days are never touched, giving a permanent daily log.
     */
    public function up(): void
    {
        Schema::create('daily_history', function (Blueprint $table) {
            $table->id();

            // The SGT calendar date this snapshot belongs to (e.g. '2026-03-02')
            $table->date('snapshot_date')->index();

            // Store identification
            $table->string('shop_id');
            $table->string('shop_name');

            // Platform summary
            $table->integer('platforms_online')->default(0);
            $table->integer('total_platforms')->default(3);

            // Offline item count (sum across all platforms)
            $table->integer('total_offline_items')->default(0);

            // Full platform details + offline items as JSON
            // Structure: { grab: { status, offline_items: [{name, sku, category, price, image_url}] }, ... }
            $table->text('platform_data')->nullable();

            // When this snapshot was last written (UTC)
            $table->timestamp('last_updated_at')->nullable();

            $table->timestamps();

            // One row per store per day
            $table->unique(['snapshot_date', 'shop_id']);
            $table->index(['snapshot_date', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_history');
    }
};
