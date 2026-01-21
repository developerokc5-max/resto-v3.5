<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Clearing test data from item_status_history table...\n";

$count = DB::table('item_status_history')->count();
echo "Found {$count} test records\n";

DB::table('item_status_history')->truncate();

echo "✓ All test history data cleared!\n";
echo "✓ The system is now ready to track REAL item status changes from your scraper\n";
