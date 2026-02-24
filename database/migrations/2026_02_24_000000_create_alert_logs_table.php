<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->string('shop_name');
            $table->enum('type', ['offline', 'recovered']);
            $table->json('platforms_affected')->nullable(); // which platforms were offline
            $table->timestamp('alerted_at');
            $table->timestamp('recovered_at')->nullable();
            $table->integer('downtime_minutes')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->index('shop_id');
            $table->index('type');
            $table->index('alerted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
