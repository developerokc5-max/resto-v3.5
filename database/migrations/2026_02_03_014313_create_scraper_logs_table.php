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
        Schema::create('scraper_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scraper_name'); // items, platform
            $table->string('status'); // success, failed, partial
            $table->integer('items_processed')->default(0);
            $table->integer('items_updated')->default(0);
            $table->text('log_message')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();
            $table->index(['scraper_name', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_logs');
    }
};
