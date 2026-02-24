<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for alert_logs (new table) and
     * remaining performance gaps in items / restosuite_item_changes.
     */
    public function up(): void
    {
        // alert_logs: composite index for the common query pattern
        // (filter by shop_id + type + unresolved alerts)
        Schema::table('alert_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('alert_logs', 'idx_alert_logs_shop_type_recovered')) {
                $table->index(['shop_id', 'type', 'recovered_at'], 'idx_alert_logs_shop_type_recovered');
            }
            if (!Schema::hasIndex('alert_logs', 'idx_alert_logs_alerted_at')) {
                $table->index('alerted_at', 'idx_alert_logs_alerted_at');
            }
        });

        // items: composite (shop_name, platform, is_available) for /store/{id}/logs
        // Speeds up the 3×-per-request offline-items query
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasIndex('items', 'idx_items_shop_platform_avail')) {
                $table->index(['shop_name', 'platform', 'is_available'], 'idx_items_shop_platform_avail');
            }
        });

        // restosuite_item_changes: composite (created_at, shop_id) for timeline queries
        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            if (!Schema::hasIndex('restosuite_item_changes', 'idx_changes_created_shop')) {
                $table->index(['created_at', 'shop_id'], 'idx_changes_created_shop');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alert_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_alert_logs_shop_type_recovered');
            $table->dropIndexIfExists('idx_alert_logs_alerted_at');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_items_shop_platform_avail');
        });

        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_changes_created_shop');
        });
    }
};
