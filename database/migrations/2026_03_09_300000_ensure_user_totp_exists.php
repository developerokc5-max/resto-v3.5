<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_totp')) {
            Schema::create('user_totp', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('secret');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // keep the table
    }
};
