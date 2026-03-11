<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── items table ───────────────────────────────────────────────
        // These columns appear in WHERE, GROUP BY, and COUNT queries on every
        // page that touches items (stores, dashboard, items, export, alerts)
        Schema::table('items', function (Blueprint $table) {
            // WHERE shop_name = ? (stores, store-detail, offline-items queries)
            if (!$this->indexExists('items', 'idx_items_shop_name')) {
                $table->index('shop_name', 'idx_items_shop_name');
            }
            // WHERE is_available = false (offline counts on every page)
            if (!$this->indexExists('items', 'idx_items_is_available')) {
                $table->index('is_available', 'idx_items_is_available');
            }
            // GROUP BY shop_name, platform (export + offline-items stats)
            if (!$this->indexExists('items', 'idx_items_shop_platform')) {
                $table->index(['shop_name', 'platform'], 'idx_items_shop_platform');
            }
            // Composite: offline items per shop (most common query pattern)
            if (!$this->indexExists('items', 'idx_items_shop_available')) {
                $table->index(['shop_name', 'is_available'], 'idx_items_shop_available');
            }
        });

        // ── platform_status table ─────────────────────────────────────
        // Hit on every page load (dashboard, platforms, stores, alerts, export)
        Schema::table('platform_status', function (Blueprint $table) {
            // WHERE shop_id = ? / GROUP BY shop_id
            if (!$this->indexExists('platform_status', 'idx_ps_shop_id')) {
                $table->index('shop_id', 'idx_ps_shop_id');
            }
            // WHERE is_online = ? (counts on dashboard and alerts)
            if (!$this->indexExists('platform_status', 'idx_ps_is_online')) {
                $table->index('is_online', 'idx_ps_is_online');
            }
            // WHERE store_name = ? (store-detail platform lookup)
            if (!$this->indexExists('platform_status', 'idx_ps_store_name')) {
                $table->index('store_name', 'idx_ps_store_name');
            }
            // Composite: groupBy shop_id + platform (export + platforms page)
            if (!$this->indexExists('platform_status', 'idx_ps_shop_platform')) {
                $table->index(['shop_id', 'platform'], 'idx_ps_shop_platform');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_shop_name');
            $table->dropIndex('idx_items_is_available');
            $table->dropIndex('idx_items_shop_platform');
            $table->dropIndex('idx_items_shop_available');
        });

        Schema::table('platform_status', function (Blueprint $table) {
            $table->dropIndex('idx_ps_shop_id');
            $table->dropIndex('idx_ps_is_online');
            $table->dropIndex('idx_ps_store_name');
            $table->dropIndex('idx_ps_shop_platform');
        });
    }

    /** Check if an index already exists (safe re-run) */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName     = $connection->getDatabaseName();

        $count = $connection->selectOne("
            SELECT COUNT(*) as cnt
            FROM pg_indexes
            WHERE tablename = ? AND indexname = ?
        ", [$table, $indexName]);

        return $count && $count->cnt > 0;
    }
};
