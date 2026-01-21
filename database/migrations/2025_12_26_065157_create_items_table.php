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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id')->index(); // Platform shop ID
            $table->string('shop_name');
            $table->string('item_id')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('platform'); // grab, foodpanda, deliveroo
            $table->string('platform_item_id')->nullable(); // Platform-specific item ID
            $table->timestamps();

            $table->index(['shop_id', 'platform']);
            $table->index(['shop_name', 'platform']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
