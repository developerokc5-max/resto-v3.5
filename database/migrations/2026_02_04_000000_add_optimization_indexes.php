<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds additional composite indexes for high-frequency query patterns
     */
    public function up(): void
    {
        // Optimize items table for dashboard queries
        Schema::table('items', function (Blueprint $table) {
            // Speed up queries filtering by shop_name + platform + availability
            $table->index(['shop_name', 'platform', 'is_available'], 'idx_items_shop_platform_availability');

            // Speed up updated_at queries (for sync checks)
            $table->index('updated_at', 'idx_items_updated_at');

            // Speed up shop_name + updated_at queries
            $table->index(['shop_name', 'updated_at'], 'idx_items_shop_updated_at');
        });

        // Optimize platform_status table for dashboard and alert queries
        Schema::table('platform_status', function (Blueprint $table) {
            // Speed up queries checking for offline stores (common alert check)
            $table->index(['is_online', 'shop_id'], 'idx_platform_status_online_shop');

            // Speed up compound queries on platform + online status
            $table->index(['platform', 'is_online'], 'idx_platform_status_platform_online');

            // Speed up date-based queries
            $table->index('last_checked_at', 'idx_platform_status_last_checked');
        });

        // Optimize restosuite_item_snapshots for dashboard queries
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            // Speed up queries by shop_id (primary dashboard query)
            $table->index('shop_id', 'idx_snapshots_shop_id');

            // Speed up queries filtering by is_active
            $table->index('is_active', 'idx_snapshots_is_active');

            // Speed up compound queries (shop_id + is_active)
            $table->index(['shop_id', 'is_active'], 'idx_snapshots_shop_active');

            // Speed up updated_at queries
            $table->index('updated_at', 'idx_snapshots_updated_at');
        });

        // Optimize restosuite_item_changes for alert and report queries
        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            // Speed up queries filtering by date (common report query)
            $table->index('created_at', 'idx_changes_created_at');

            // Speed up queries filtering by shop_id
            $table->index('shop_id', 'idx_changes_shop_id');

            // Speed up compound queries (shop_id + created_at)
            $table->index(['shop_id', 'created_at'], 'idx_changes_shop_created');
        });

        // Optimize store_status_logs for report queries
        Schema::table('store_status_logs', function (Blueprint $table) {
            // Already has composite index on shop_id, logged_at
            // Add index for queries using just logged_at
            $table->index(['logged_at', 'status'], 'idx_logs_logged_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_shop_platform_availability');
            $table->dropIndex('idx_items_updated_at');
            $table->dropIndex('idx_items_shop_updated_at');
        });

        Schema::table('platform_status', function (Blueprint $table) {
            $table->dropIndex('idx_platform_status_online_shop');
            $table->dropIndex('idx_platform_status_platform_online');
            $table->dropIndex('idx_platform_status_last_checked');
        });

        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            $table->dropIndex('idx_snapshots_shop_id');
            $table->dropIndex('idx_snapshots_is_active');
            $table->dropIndex('idx_snapshots_shop_active');
            $table->dropIndex('idx_snapshots_updated_at');
        });

        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            $table->dropIndex('idx_changes_created_at');
            $table->dropIndex('idx_changes_shop_id');
            $table->dropIndex('idx_changes_shop_created');
        });

        Schema::table('store_status_logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_logged_status');
        });
    }
};
