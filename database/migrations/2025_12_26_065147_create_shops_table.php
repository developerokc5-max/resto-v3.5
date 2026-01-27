<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id')->unique(); // RestoSuite outlet ID
            $table->string('shop_name');
            $table->string('organization_name')->nullable();
            $table->boolean('has_items')->default(false); // Track if outlet has any items
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('shop_id');
            $table->index('shop_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
