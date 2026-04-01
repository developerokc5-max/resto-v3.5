<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('shop_name');
        });

        // Backfill existing rows using the same " @ " extraction logic
        $shops = DB::table('shops')->get();
        foreach ($shops as $shop) {
            $brand = str_contains($shop->shop_name, ' @ ')
                ? trim(explode(' @ ', $shop->shop_name)[0])
                : ($shop->organization_name ?? $shop->shop_name);

            DB::table('shops')->where('shop_id', $shop->shop_id)->update(['brand' => $brand]);
        }
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }
};
