<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // SNAPSHOTS
        if (Schema::hasTable('restosuite_item_snapshots')) {
            Schema::table('restosuite_item_snapshots', function (Blueprint $table) {

                if (!Schema::hasColumn('restosuite_item_snapshots', 'shop_id')) {
                    $table->string('shop_id')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'item_uid')) {
                    $table->string('item_uid')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'item_id')) {
                    $table->string('item_id')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'name')) {
                    $table->string('name')->nullable();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'is_active')) {
                    $table->integer('is_active')->nullable();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'price')) {
                    $table->string('price')->nullable();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'status')) {
                    $table->string('status')->nullable();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'raw_json')) {
                    $table->longText('raw_json')->nullable();
                }

                if (!Schema::hasColumn('restosuite_item_snapshots', 'run_id')) {
                    $table->string('run_id')->nullable()->index();
                }
            });
        }

        // CHANGES
        if (Schema::hasTable('restosuite_item_changes')) {
            Schema::table('restosuite_item_changes', function (Blueprint $table) {

                if (!Schema::hasColumn('restosuite_item_changes', 'shop_id')) {
                    $table->string('shop_id')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_changes', 'item_uid')) {
                    $table->string('item_uid')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_changes', 'item_id')) {
                    $table->string('item_id')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_changes', 'run_id')) {
                    $table->string('run_id')->nullable()->index();
                }

                if (!Schema::hasColumn('restosuite_item_changes', 'change_json')) {
                    $table->longText('change_json')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // IMPORTANT:
        // On SQLite, dropping columns is fragile.
        // So we ONLY drop columns if they exist. Otherwise rollback crashes.

        if (Schema::hasTable('restosuite_item_snapshots')) {
            Schema::table('restosuite_item_snapshots', function (Blueprint $table) {

                if (Schema::hasColumn('restosuite_item_snapshots', 'shop_id')) {
                    $table->dropColumn('shop_id');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'item_uid')) {
                    $table->dropColumn('item_uid');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'item_id')) {
                    $table->dropColumn('item_id');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'name')) {
                    $table->dropColumn('name');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'is_active')) {
                    $table->dropColumn('is_active');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'price')) {
                    $table->dropColumn('price');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'status')) {
                    $table->dropColumn('status');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'raw_json')) {
                    $table->dropColumn('raw_json');
                }

                if (Schema::hasColumn('restosuite_item_snapshots', 'run_id')) {
                    $table->dropColumn('run_id');
                }
            });
        }

        if (Schema::hasTable('restosuite_item_changes')) {
            Schema::table('restosuite_item_changes', function (Blueprint $table) {

                if (Schema::hasColumn('restosuite_item_changes', 'shop_id')) {
                    $table->dropColumn('shop_id');
                }

                if (Schema::hasColumn('restosuite_item_changes', 'item_uid')) {
                    $table->dropColumn('item_uid');
                }

                if (Schema::hasColumn('restosuite_item_changes', 'item_id')) {
                    $table->dropColumn('item_id');
                }

                if (Schema::hasColumn('restosuite_item_changes', 'run_id')) {
                    $table->dropColumn('run_id');
                }

                if (Schema::hasColumn('restosuite_item_changes', 'change_json')) {
                    $table->dropColumn('change_json');
                }
            });
        }
    }
};
