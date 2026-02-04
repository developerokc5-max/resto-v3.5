<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing indexes for report queries and date filtering
     * Optimizes Daily Trends, Platform Reliability, and Item Performance reports
     */
    public function up(): void
    {
        // Optimize items table for availability and category filtering
        Schema::table('items', function (Blueprint $table) {
            // Speed up offline items queries
            if (!Schema::hasIndex('items', 'idx_items_availability')) {
                $table->index('is_available', 'idx_items_availability');
            }

            // Speed up category performance queries
            if (!Schema::hasIndex('items', 'idx_items_category')) {
                $table->index('category', 'idx_items_category');
            }

            // Composite index for category + availability (common filtering combination)
            if (!Schema::hasIndex('items', 'idx_items_category_availability')) {
                $table->index(['category', 'is_available'], 'idx_items_category_availability');
            }

            // Speed up updated_at filtering for recent offline items
            if (!Schema::hasIndex('items', 'idx_items_updated_at')) {
                $table->index('updated_at', 'idx_items_updated_at');
            }

            // Composite for shop_name + availability (used in reports)
            if (!Schema::hasIndex('items', 'idx_items_shop_availability')) {
                $table->index(['shop_name', 'is_available'], 'idx_items_shop_availability');
            }
        });

        // Optimize platform_status table for date-based queries
        Schema::table('platform_status', function (Blueprint $table) {
            // Speed up last_checked_at filtering in reports
            if (!Schema::hasIndex('platform_status', 'idx_platform_status_last_checked')) {
                $table->index('last_checked_at', 'idx_platform_status_last_checked');
            }

            // Speed up is_online filtering for uptime calculations
            if (!Schema::hasIndex('platform_status', 'idx_platform_status_is_online')) {
                $table->index('is_online', 'idx_platform_status_is_online');
            }

            // Composite for platform + is_online (used in platform reliability report)
            if (!Schema::hasIndex('platform_status', 'idx_platform_status_platform_online')) {
                $table->index(['platform', 'is_online'], 'idx_platform_status_platform_online');
            }
        });

        // Optimize store_status_logs table for date range queries
        Schema::table('store_status_logs', function (Blueprint $table) {
            // Speed up logged_at filtering for daily/weekly trends
            if (!Schema::hasIndex('store_status_logs', 'idx_logs_logged_at')) {
                $table->index('logged_at', 'idx_logs_logged_at');
            }

            // Composite for shop_id + logged_at (common trend queries)
            if (!Schema::hasIndex('store_status_logs', 'idx_logs_shop_logged_at')) {
                $table->index(['shop_id', 'logged_at'], 'idx_logs_shop_logged_at');
            }

            // Speed up status filtering (online/offline logs)
            if (!Schema::hasIndex('store_status_logs', 'idx_logs_status')) {
                $table->index('status', 'idx_logs_status');
            }

            // Composite for complex trend queries
            if (!Schema::hasIndex('store_status_logs', 'idx_logs_shop_logged_status')) {
                $table->index(['shop_id', 'logged_at', 'status'], 'idx_logs_shop_logged_status');
            }
        });

        // Optimize restosuite_item_snapshots for query aggregations
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            // Speed up updated_at filtering
            if (!Schema::hasIndex('restosuite_item_snapshots', 'idx_snapshots_updated_at')) {
                $table->index('updated_at', 'idx_snapshots_updated_at');
            }

            // Composite for common aggregations
            if (!Schema::hasIndex('restosuite_item_snapshots', 'idx_snapshots_shop_updated')) {
                $table->index(['shop_id', 'updated_at'], 'idx_snapshots_shop_updated');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_items_availability');
            $table->dropIndexIfExists('idx_items_category');
            $table->dropIndexIfExists('idx_items_category_availability');
            $table->dropIndexIfExists('idx_items_updated_at');
            $table->dropIndexIfExists('idx_items_shop_availability');
        });

        Schema::table('platform_status', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_platform_status_last_checked');
            $table->dropIndexIfExists('idx_platform_status_is_online');
            $table->dropIndexIfExists('idx_platform_status_platform_online');
        });

        Schema::table('store_status_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_logs_logged_at');
            $table->dropIndexIfExists('idx_logs_shop_logged_at');
            $table->dropIndexIfExists('idx_logs_status');
            $table->dropIndexIfExists('idx_logs_shop_logged_status');
        });

        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_snapshots_updated_at');
            $table->dropIndexIfExists('idx_snapshots_shop_updated');
        });
    }
};
