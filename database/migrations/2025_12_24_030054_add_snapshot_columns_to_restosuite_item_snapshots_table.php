<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('restosuite_item_snapshots', 'run_id')) {
                $table->string('run_id', 32)->index();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'shop_id')) {
                $table->string('shop_id', 32)->index();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'item_id')) {
                $table->string('item_id', 64)->index();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'is_active')) {
                $table->integer('is_active')->nullable(); // 1/0 from API
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'status')) {
                $table->string('status')->nullable(); // optional
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'price')) {
                $table->string('price')->nullable(); // keep string, API sometimes returns ""
            }
            if (!Schema::hasColumn('restosuite_item_snapshots', 'raw_json')) {
                $table->longText('raw_json')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('restosuite_item_snapshots', function (Blueprint $table) {
            foreach (['run_id','shop_id','item_id','name','is_active','status','price','raw_json'] as $col) {
                if (Schema::hasColumn('restosuite_item_snapshots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
