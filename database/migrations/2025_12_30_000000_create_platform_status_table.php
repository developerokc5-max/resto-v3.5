<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_status', function (Blueprint $table) {
            $table->id();

            // Store identification
            $table->string('shop_id')->index();

            // Platform identification (grab, foodpanda, deliveroo)
            $table->string('platform', 20)->index();

            // Platform status
            $table->boolean('is_online')->default(false);
            $table->integer('items_synced')->default(0);
            $table->integer('items_total')->default(0);

            // Additional metadata
            $table->string('store_name')->nullable();
            $table->string('store_url')->nullable();

            // Scraping metadata
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_status')->nullable(); // 'success', 'failed', 'error'
            $table->text('last_error')->nullable();

            // Raw data for debugging
            $table->longText('raw_html')->nullable();

            $table->timestamps();

            // Composite unique index (one status per shop per platform)
            $table->unique(['shop_id', 'platform']);

            // Index for quick lookups
            $table->index(['shop_id', 'is_online']);
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_status');
    }
};
