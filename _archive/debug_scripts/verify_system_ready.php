#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" SYSTEM READINESS CHECK ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

$checks = [];
$allGood = true;

// Check 1: Database connection
echo "1. Database Connection...";
try {
    DB::connection()->getPdo();
    echo " ✅\n";
    $checks[] = ['Database', '✅ Connected'];
} catch (\Exception $e) {
    echo " ❌\n";
    echo "   Error: " . $e->getMessage() . "\n";
    $checks[] = ['Database', '❌ Failed'];
    $allGood = false;
}

// Check 2: Items table exists
echo "2. Items Table...";
try {
    $count = DB::table('items')->count();
    echo " ✅ ({$count} items)\n";
    $checks[] = ['Items Table', "✅ Exists ({$count} items)"];
} catch (\Exception $e) {
    echo " ❌\n";
    $checks[] = ['Items Table', '❌ Missing'];
    $allGood = false;
}

// Check 3: History table exists
echo "3. History Table...";
try {
    $count = DB::table('item_status_history')->count();
    echo " ✅ ({$count} records)\n";
    $checks[] = ['History Table', "✅ Exists ({$count} records)"];
} catch (\Exception $e) {
    echo " ❌\n";
    $checks[] = ['History Table', '❌ Missing'];
    $allGood = false;
}

// Check 4: Python scrapers exist
echo "4. Python Scrapers...";
$scrapers = [
    'scrape_restosuite_production.py',
    'scrape_platforms.py',
    'test_platform_scraper.py'
];
$scraperCount = 0;
foreach ($scrapers as $scraper) {
    if (file_exists(__DIR__ . '/' . $scraper)) {
        $scraperCount++;
    }
}
if ($scraperCount === count($scrapers)) {
    echo " ✅ (All {$scraperCount} present)\n";
    $checks[] = ['Python Scrapers', "✅ All present ({$scraperCount})"];
} else {
    echo " ⚠️ ({$scraperCount}/" . count($scrapers) . ")\n";
    $checks[] = ['Python Scrapers', "⚠️ Some missing ({$scraperCount}/" . count($scrapers) . ")"];
}

// Check 5: Laravel commands
echo "5. Laravel Commands...";
$commands = [
    'scrape:restosuite-production',
    'scrape:platforms'
];
$commandCount = 0;
foreach ($commands as $command) {
    try {
        $output = shell_exec("php artisan list 2>&1 | grep -c '{$command}'");
        if (intval($output) > 0) {
            $commandCount++;
        }
    } catch (\Exception $e) {
        // Command check failed
    }
}
if ($commandCount === count($commands)) {
    echo " ✅ (All registered)\n";
    $checks[] = ['Laravel Commands', '✅ All registered'];
} else {
    echo " ⚠️ ({$commandCount}/" . count($commands) . ")\n";
    $checks[] = ['Laravel Commands', "⚠️ Some missing ({$commandCount}/" . count($commands) . ")"];
}

// Check 6: Python & dependencies
echo "6. Python & Dependencies...";
$pythonCheck = shell_exec('python --version 2>&1');
if (strpos($pythonCheck, 'Python') !== false) {
    echo " ✅\n";
    $checks[] = ['Python', '✅ Installed'];

    // Check playwright
    echo "   - Checking Playwright...";
    $playwrightCheck = shell_exec('python -c "import playwright" 2>&1');
    if ($playwrightCheck === null || $playwrightCheck === '') {
        echo " ✅\n";
        $checks[] = ['  Playwright', '✅ Installed'];
    } else {
        echo " ❌\n";
        echo "     Run: pip install playwright && playwright install chromium\n";
        $checks[] = ['  Playwright', '❌ Not installed'];
        $allGood = false;
    }

    // Check mysqlclient
    echo "   - Checking MySQLdb...";
    $mysqlCheck = shell_exec('python -c "import MySQLdb" 2>&1');
    if ($mysqlCheck === null || $mysqlCheck === '') {
        echo " ✅\n";
        $checks[] = ['  MySQLdb', '✅ Installed'];
    } else {
        echo " ❌\n";
        echo "     Run: pip install mysqlclient\n";
        $checks[] = ['  MySQLdb', '❌ Not installed'];
        $allGood = false;
    }
} else {
    echo " ❌\n";
    $checks[] = ['Python', '❌ Not found'];
    $allGood = false;
}

// Check 7: Current image coverage
echo "7. Current Image Coverage...";
try {
    $totalItems = DB::table('items')->count();
    $withImages = DB::table('items')
        ->whereNotNull('image_url')
        ->where('image_url', '!=', '')
        ->count();

    $percentage = $totalItems > 0 ? round(($withImages / $totalItems) * 100, 1) : 0;

    if ($percentage > 50) {
        echo " ✅ ({$percentage}%)\n";
        $checks[] = ['Image Coverage', "✅ {$percentage}%"];
    } elseif ($percentage > 0) {
        echo " ⚠️ ({$percentage}%)\n";
        $checks[] = ['Image Coverage', "⚠️ {$percentage}% (run scraper!)"];
    } else {
        echo " ❌ (0%)\n";
        echo "   → Run scraper to populate images!\n";
        $checks[] = ['Image Coverage', '❌ 0% (needs scraping)'];
    }
} catch (\Exception $e) {
    echo " ❌\n";
    $checks[] = ['Image Coverage', '❌ Error checking'];
}

// Check 8: Scheduler
echo "8. Task Scheduler...";
try {
    // Check if schedule:work or cron is running
    $scheduleCheck = shell_exec("php artisan schedule:list 2>&1 | grep -c 'scrape:platforms'");
    if (intval($scheduleCheck) > 0) {
        echo " ✅ (Configured)\n";
        $checks[] = ['Scheduler', '✅ Configured'];
        echo "   → Run: php artisan schedule:work (to enable)\n";
    } else {
        echo " ⚠️ (Not configured)\n";
        $checks[] = ['Scheduler', '⚠️ Not configured'];
    }
} catch (\Exception $e) {
    echo " ⚠️\n";
    $checks[] = ['Scheduler', '⚠️ Unknown'];
}

// Summary
echo "\n";
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" SUMMARY ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

foreach ($checks as $check) {
    echo sprintf("  %-25s %s\n", $check[0] . ':', $check[1]);
}

echo "\n";

if ($allGood) {
    echo "╔" . str_repeat("═", 78) . "╗\n";
    echo "║" . str_pad(" ✅ SYSTEM READY! ", 78, " ", STR_PAD_BOTH) . "║\n";
    echo "╚" . str_repeat("═", 78) . "╝\n\n";

    echo "🚀 You can now run:\n";
    echo "   php artisan scrape:restosuite-production\n\n";
} else {
    echo "╔" . str_repeat("═", 78) . "╗\n";
    echo "║" . str_pad(" ⚠️ SOME ISSUES FOUND ", 78, " ", STR_PAD_BOTH) . "║\n";
    echo "╚" . str_repeat("═", 78) . "╝\n\n";

    echo "Please fix the issues above before running the scraper.\n\n";
}

echo "📚 Documentation:\n";
echo "   PRODUCTION_READY_SUMMARY.md - Overview\n";
echo "   SCRAPER_GUIDE.md            - Detailed guide\n";
echo "   WEBSCRAPE_PRODUCTION.md     - Platform scraper docs\n\n";
