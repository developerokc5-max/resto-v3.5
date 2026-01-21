<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restosuite_item_snapshots', function (Blueprint $table) {
            $table->id();

            // run + identity
            $table->string('run_id')->index();
            $table->string('shop_id')->index();
            $table->string('item_id')->index();     // NOT NULL (used everywhere)
            $table->string('item_uid')->index();    // same as item_id, kept for safety

            // item state
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(0);
            $table->string('status')->nullable();
            $table->string('price')->nullable();
            $table->text('image_url')->nullable();

            // fingerprint to skip duplicates
            $table->string('fingerprint', 64)->nullable()->index();

            // full payload
            $table->longText('raw_json')->nullable();

            $table->timestamps();

            // optional safety index
            $table->index(['shop_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restosuite_item_snapshots');
    }
};
