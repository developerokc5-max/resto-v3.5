<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ghosts = ['Madas @ Taman Jurong'];

        foreach ($ghosts as $shopId) {
            DB::table('items')->where('shop_id', $shopId)->delete();
            DB::table('item_status_history')->where('shop_id', $shopId)->delete();
            DB::table('store_status_logs')->where('shop_id', $shopId)->delete();
            DB::table('restosuite_item_snapshots')->where('shop_id', $shopId)->delete();
            DB::table('shops')->where('shop_id', $shopId)->delete();
        }
    }

    public function down(): void
    {
        // Ghost shops are not restored on rollback
    }
};
