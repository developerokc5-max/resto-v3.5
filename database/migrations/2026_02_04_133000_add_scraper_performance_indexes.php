<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds specialized indexes for the scraper's outlet scanning phase
     * These indexes optimize the initial 70-second database scan bottleneck
     */
    public function up(): void
    {
        // Optimize shops table for scraper outlet enumeration
        Schema::table('shops', function (Blueprint $table) {
            // Speed up the "find all active outlets" query
            // This is the primary bottleneck (70 seconds for 46 outlets)
            if (!Schema::hasIndex('shops', 'idx_shops_active_brand')) {
                $table->index(['is_active', 'brand'], 'idx_shops_active_brand');
            }

            // Speed up queries filtering outlets by status
            if (!Schema::hasIndex('shops', 'idx_shops_status')) {
                $table->index('status', 'idx_shops_status');
            }

            // Speed up searches by shop name (scraper may query by name)
            if (!Schema::hasIndex('shops', 'idx_shops_name')) {
                $table->index('name', 'idx_shops_name');
            }

            // Composite index for (is_active, status, created_at) - common filtering
            if (!Schema::hasIndex('shops', 'idx_shops_active_status_created')) {
                $table->index(['is_active', 'status', 'created_at'], 'idx_shops_active_status_created');
            }
        });

        // Optimize platform_status table for outlet enumeration
        Schema::table('platform_status', function (Blueprint $table) {
            // Speed up joins with shops table
            // Scraper queries like: SELECT * FROM shops JOIN platform_status USING shop_id
            if (!Schema::hasIndex('platform_status', 'idx_platform_status_shop_id')) {
                $table->index('shop_id', 'idx_platform_status_shop_id');
            }

            // Composite for (shop_id, platform) - common scraper query
            if (!Schema::hasIndex('platform_status', 'idx_platform_status_shop_platform')) {
                $table->index(['shop_id', 'platform'], 'idx_platform_status_shop_platform');
            }
        });

        // Optimize restosuite_snapshots for item counting during scraping
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            // Speed up COUNT queries during outlet scanning
            if (!Schema::hasIndex('restosuite_item_snapshots', 'idx_snapshots_shop_brand')) {
                $table->index(['shop_id', 'brand'], 'idx_snapshots_shop_brand');
            }
        });

        // Optimize store_status_logs for performance monitoring
        Schema::table('store_status_logs', function (Blueprint $table) {
            // Speed up recent status queries
            if (!Schema::hasIndex('store_status_logs', 'idx_logs_shop_recent')) {
                $table->index(['shop_id', 'logged_at'], 'idx_logs_shop_recent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_shops_active_brand');
            $table->dropIndexIfExists('idx_shops_status');
            $table->dropIndexIfExists('idx_shops_name');
            $table->dropIndexIfExists('idx_shops_active_status_created');
        });

        Schema::table('platform_status', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_platform_status_shop_id');
            $table->dropIndexIfExists('idx_platform_status_shop_platform');
        });

        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_snapshots_shop_brand');
        });

        Schema::table('store_status_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_logs_shop_recent');
        });
    }
};
