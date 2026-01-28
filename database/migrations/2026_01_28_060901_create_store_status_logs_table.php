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
        Schema::create('store_status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->string('shop_name');
            $table->integer('platforms_online')->default(0);
            $table->integer('total_platforms')->default(3);
            $table->integer('total_offline_items')->default(0);
            $table->text('platform_data')->nullable(); // JSON data for platform details
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['shop_id', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_status_logs');
    }
};
