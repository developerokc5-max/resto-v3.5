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
        Schema::create('item_status_history', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('shop_id');
            $table->string('shop_name');
            $table->string('platform'); // grab, foodpanda, deliveroo
            $table->boolean('is_available'); // true = online, false = offline
            $table->decimal('price', 10, 2)->nullable();
            $table->string('category')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('changed_at'); // When the status changed
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['shop_id', 'platform', 'changed_at']);
            $table->index('item_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_status_history');
    }
};
