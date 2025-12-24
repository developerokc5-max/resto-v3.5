<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restosuite_item_changes', function (Blueprint $table) {
            $table->id();

            // run + identity
            $table->string('run_id')->index();
            $table->string('shop_id')->index();
            $table->string('item_id')->index();
            $table->string('item_uid')->index();

            // diff only (no snapshots here)
            $table->json('change_json');

            $table->timestamps();

            $table->index(['shop_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restosuite_item_changes');
    }
};
