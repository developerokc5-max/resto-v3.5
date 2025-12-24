<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('restosuite_item_snapshots', 'run_id')) {
                $table->string('run_id')->nullable()->index();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'raw_json')) {
                $table->text('raw_json')->nullable();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'item_uid')) {
                $table->string('item_uid')->nullable()->index();
            }
        });

        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            if (!Schema::hasColumn('restosuite_item_changes', 'run_id')) {
                $table->string('run_id')->nullable()->index();
            }
            if (!Schema::hasColumn('restosuite_item_changes', 'change_json')) {
                $table->text('change_json')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('restosuite_item_snapshots', 'run_id')) $table->dropColumn('run_id');
            if (Schema::hasColumn('restosuite_item_snapshots', 'raw_json')) $table->dropColumn('raw_json');
            if (Schema::hasColumn('restosuite_item_snapshots', 'item_uid')) $table->dropColumn('item_uid');
        });

        Schema::table('restosuite_item_changes', function (Blueprint $table) {
            if (Schema::hasColumn('restosuite_item_changes', 'run_id')) $table->dropColumn('run_id');
            if (Schema::hasColumn('restosuite_item_changes', 'change_json')) $table->dropColumn('change_json');
        });
    }
};
