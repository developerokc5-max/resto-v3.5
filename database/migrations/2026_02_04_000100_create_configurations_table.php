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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting key (e.g., 'scraper_interval')
            $table->text('value'); // Setting value (e.g., 'every_10_minutes')
            $table->string('type')->default('string'); // Type: string, boolean, number, email
            $table->text('description')->nullable(); // Human-readable description
            $table->timestamps();

            // Add index for quick lookups
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
