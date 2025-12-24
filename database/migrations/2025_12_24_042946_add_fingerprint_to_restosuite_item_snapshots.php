<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('restosuite_item_snapshots')) {
            return;
        }

        // Add column only if missing
        if (!Schema::hasColumn('restosuite_item_snapshots', 'fingerprint')) {
            Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
                $table->string('fingerprint', 64)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('restosuite_item_snapshots')) {
            return;
        }

        // IMPORTANT for SQLite:
        // Drop index first (if the column exists), then drop column
        if (Schema::hasColumn('restosuite_item_snapshots', 'fingerprint')) {
            Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
                // Index name that Laravel likely generated
                // (if it doesn't exist, it will throw — but only runs when column exists)
                $table->dropIndex(['fingerprint']);
                $table->dropColumn('fingerprint');
            });
        }
    }
};
