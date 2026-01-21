# Platform Monitoring System

## Overview
Real-time monitoring system for restaurant platform status (Grab, FoodPanda, Deliveroo) using Playwright web scraping.

## Architecture

### 1. Playwright Scraper (`scrape_restosuite_playwright.py`)
- Logs into RestoSuite backend (https://bo.sea.restosuite.ai)
- Scrapes platform toggle states for all 44 shops
- Uses headless Chromium browser
- Execution time: ~55-60 seconds
- Output: JSON with shop bindings and toggle states

**Key Features:**
- Multi-step login (email → password)
- Platform tab switching (Grab, Deliveroo, FoodPanda)
- Pagination (100 items per page)
- Platform ID verification to ensure correct tab data
- Toggle state detection via `aria-checked` attribute

### 2. Background Cache Script (`cache_platform_data.php`)
- Runs the Playwright scraper in background
- Saves output to `storage/app/platform_data_cache.json`
- Updates database via `php artisan scrape:platform-status`
- Avoids 30-second web request timeout

**Usage:**
```bash
php cache_platform_data.php
```

### 3. Platform Status Service (`app/Services/PlatformScrapingService.php`)
- Loads cached data from JSON file
- Provides platform status for each shop
- Used by dashboard and platform pages

### 4. Web Routes
- `/platforms` - Platform monitoring page (shows all 44 shops)
- Reads directly from cache file for real-time display

## Current Data (as of last scrape)

**Total Shops:** 44 per platform (132 total platform bindings)

**Status Breakdown:**
- Grab: 35/44 online (79.5%)
- Deliveroo: 31/44 online (70.5%)
- FoodPanda: 35/44 online (79.5%)

**Overall:** 101/132 online (76.5%)

## How to Update Data

1. Run the background scraper:
```bash
php cache_platform_data.php
```

2. The cache file will be updated with fresh data

3. Refresh http://127.0.0.1:8000/platforms to see updated status

## Automated Updates (Recommended)

Set up a scheduled task to run every 5-15 minutes:

**Windows Task Scheduler:**
- Action: `php C:\resto-db-v3\cache_platform_data.php`
- Trigger: Every 10 minutes

**Laravel Scheduler (in `app/Console/Kernel.php`):**
```php
protected function schedule(Schedule $schedule)
{
    $schedule->exec('php ' . base_path('cache_platform_data.php'))
        ->everyTenMinutes();
}
```

Then run: `php artisan schedule:work`

## Platform ID Formats (for verification)

- **Grab**: Starts with `4-C` (e.g., `4-C7VBGYXAA8MZG6`)
- **Deliveroo**: 5-7 digit numbers (e.g., `758706`)
- **FoodPanda**: 9-digit numbers (e.g., `408759190`)

## Files Structure

```
C:\resto-db-v3\
├── cache_platform_data.php              # Background scraper runner
├── scrape_restosuite_playwright.py      # Playwright scraper
├── storage/app/
│   └── platform_data_cache.json         # Cached scraper data
├── app/Services/
│   └── PlatformScrapingService.php      # Platform status service
└── routes/web.php                        # Platform page route
```

## Removed Files (Cleanup)

The following old/test files have been removed:
- `scrape_restosuite.py` (old Selenium version)
- `test-binding.php`
- `test-platform-data.php`
- `test-web-scraper.php`
- `current_scrape.json`
- `platform_data.json`
- `le_le_mee_pok_toa_payoh.json`
- `store_*.json` (test data)
- `config/platform-mappings.php` (static config)
- `app/Services/RestoSuiteWebScraper.php` (unused)

## Dependencies

**Python:**
- playwright
- Install: `pip install playwright`
- Setup: `playwright install chromium`

**PHP:**
- Laravel 11
- GuzzleHTTP (for API calls)

## Troubleshooting

**Cache file not found:**
```bash
php cache_platform_data.php
```

**Playwright not installed:**
```bash
pip install playwright
playwright install chromium
```

**Server not running:**
```bash
php artisan serve
```

**Platform page shows errors:**
- Check `storage/logs/laravel.log`
- Verify cache file exists: `ls -lh storage/app/platform_data_cache.json`
