# Platform Status Scraper

This folder contains the platform status scraping system for monitoring outlet online/offline status across Grab, FoodPanda, and Deliveroo.

## Files

### `scrape_platform_sync.py`
Main scraper that:
- Logs into RestoSuite
- Navigates to Store Binding page
- Scans all 3 platforms (Grab, FoodPanda, Deliveroo)
- Saves online/offline status to `platform_status` table
- Logs all activity to `scrape_platform_sync.log`

### `scrape_platform_sync.log`
Activity log showing:
- Login status
- Navigation progress
- Platform scanning results
- Database updates
- Any errors encountered

## Database

**Table:** `platform_status`

**Columns:**
- `shop_id` - Outlet identifier
- `store_name` - Outlet name
- `platform` - Platform name (grab/foodpanda/deliveroo)
- `is_online` - Online status (1 = online, 0 = offline)
- `items_synced` - Number of items synced (future use)
- `last_checked_at` - Timestamp of last check
- `created_at` - Record creation timestamp
- `updated_at` - Record update timestamp

## How It Works

1. **Trigger:** User clicks "Run Sync" button on Platforms page
2. **API Call:** `/api/sync/scrape` endpoint is called
3. **Scraper Runs:** `scrape_platform_sync.py` executes in background
4. **Data Saved:** Status saved directly to `platform_status` table
5. **Page Displays:** Platforms page reads from `platform_status` table

## Paths

All paths are relative to this folder:
- **Database:** `../database/database.sqlite`
- **Log File:** `./scrape_platform_sync.log`
- **Screenshots:** `./debug_*.png` (debugging only)

## Configuration

**Environment Variables:**
- `RESTOSUITE_EMAIL` - RestoSuite login email (default: okchickenrice2018@gmail.com)
- `RESTOSUITE_PASSWORD` - RestoSuite password

**Settings:**
- **Headless Mode:** `True` (runs in background)
- **Slow Motion:** `800ms` (for stability)
- **Viewport:** `1920x1080`

## Running Manually

```bash
cd platform-test-trait-1
python scrape_platform_sync.py
```

Check the log file to see progress:
```bash
tail -f scrape_platform_sync.log
```

## Connected Files

**Frontend:**
- `resources/views/platforms.blade.php` - Platform Status page UI

**Backend:**
- `routes/api.php` (line 110) - API endpoint `/api/sync/scrape`
- `routes/web.php` - `/platforms` route

**Layout:**
- `resources/views/layout.blade.php` - "Run Sync" button logic

**Model:**
- `app/Models/PlatformStatus.php` - Eloquent model

**Migration:**
- `database/migrations/2025_12_30_000000_create_platform_status_table.php`
