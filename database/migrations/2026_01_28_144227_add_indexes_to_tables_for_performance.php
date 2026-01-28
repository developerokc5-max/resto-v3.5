<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to items table for faster queries
        Schema::table('items', function (Blueprint $table) {
            // Speed up filtering by shop
            $table->index('shop_id', 'idx_items_shop_id');

            // Speed up filtering by platform
            $table->index('platform', 'idx_items_platform');

            // Speed up filtering by availability
            $table->index('is_available', 'idx_items_is_available');

            // Speed up filtering by shop + platform (common query)
            $table->index(['shop_id', 'platform'], 'idx_items_shop_platform');

            // Speed up filtering by shop + availability
            $table->index(['shop_id', 'is_available'], 'idx_items_shop_availability');

            // Speed up searching by name
            $table->index('name', 'idx_items_name');

            // Speed up category filtering
            $table->index('category', 'idx_items_category');
        });

        // Add indexes to platform_status table
        Schema::table('platform_status', function (Blueprint $table) {
            // Speed up queries by shop
            $table->index('shop_id', 'idx_platform_status_shop_id');

            // Speed up queries by platform
            $table->index('platform', 'idx_platform_status_platform');

            // Speed up queries by online status
            $table->index('is_online', 'idx_platform_status_is_online');

            // Speed up compound queries
            $table->index(['shop_id', 'platform'], 'idx_platform_status_shop_platform');
        });

        // Add indexes to store_status_logs table
        Schema::table('store_status_logs', function (Blueprint $table) {
            // Already has composite index on shop_id, logged_at
            // Add index for date-based queries
            $table->index('logged_at', 'idx_store_logs_logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_shop_id');
            $table->dropIndex('idx_items_platform');
            $table->dropIndex('idx_items_is_available');
            $table->dropIndex('idx_items_shop_platform');
            $table->dropIndex('idx_items_shop_availability');
            $table->dropIndex('idx_items_name');
            $table->dropIndex('idx_items_category');
        });

        Schema::table('platform_status', function (Blueprint $table) {
            $table->dropIndex('idx_platform_status_shop_id');
            $table->dropIndex('idx_platform_status_platform');
            $table->dropIndex('idx_platform_status_is_online');
            $table->dropIndex('idx_platform_status_shop_platform');
        });

        Schema::table('store_status_logs', function (Blueprint $table) {
            $table->dropIndex('idx_store_logs_logged_at');
        });
    }
};
