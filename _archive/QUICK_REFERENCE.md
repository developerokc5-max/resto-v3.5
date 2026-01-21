# Quick Reference Guide

## Running Production Scrapers

### Scrape All Items (Full Database Update)
```bash
cd /c/resto-db-v3
python _archive/scrapers/scrape_complete_final.py
```
**Output:** `_archive/json_data/items_complete.json` (7,374 items)

**Then import to database:**
```bash
php _archive/php_utils/import_scraped_items.php
```

### Scrape Platform Status
```bash
cd /c/resto-db-v3
python _archive/scrapers/scrape_platform_bulletproof.py
```
Updates `platform_status` table with online/offline status for all stores.

## Database Operations

### Verify Database
```bash
php _archive/php_utils/verify_database.php
```
Shows:
- Total records (7,374)
- Unique items (2,484)
- Stores (36)
- Platform breakdown
- Data quality stats

### Check Images
```bash
php _archive/php_utils/check_images.php
```
Validates image URLs for all items.

## Important File Locations

### Production Data
- **Items JSON**: `_archive/json_data/items_complete.json` (2.8MB)
- **SQLite Database**: `database/database.sqlite`

### Scrapers
- **Main Item Scraper**: `_archive/scrapers/scrape_complete_final.py`
- **Platform Scraper**: `_archive/scrapers/scrape_platform_bulletproof.py`

### Import Tools
- **Import Items**: `_archive/php_utils/import_scraped_items.php`
- **Verify DB**: `_archive/php_utils/verify_database.php`

### Documentation
- **Items Status**: `_archive/docs/ITEMS_PAGE_STATUS.md`
- **Deployment**: `_archive/docs/DEPLOYMENT.md`
- **Setup**: `_archive/docs/SETUP.md`

## Web Pages

- **Dashboard**: http://127.0.0.1:8000/
- **Stores**: http://127.0.0.1:8000/stores
- **Items**: http://127.0.0.1:8000/items (2,484 items, 50 per page)
- **Platforms**: http://127.0.0.1:8000/platforms
- **History**: http://127.0.0.1:8000/item-tracking

## API Endpoints

### Trigger Scraping
```bash
# Scrape platforms
curl -X POST http://127.0.0.1:8000/api/sync/scrape

# Scrape items
curl -X POST http://127.0.0.1:8000/api/sync/scrape-items
```

### Get Data
```bash
# Platform status
curl http://127.0.0.1:8000/api/platform/status

# Items list
curl http://127.0.0.1:8000/api/items/list

# Health check
curl http://127.0.0.1:8000/api/health
```

## Development Server

```bash
# Start server
php artisan serve

# Or in background
php artisan serve > /dev/null 2>&1 &
```

## Common Tasks

### Update Items from Scraping
1. Run scraper: `python _archive/scrapers/scrape_complete_final.py`
2. Import data: `php _archive/php_utils/import_scraped_items.php`
3. Verify: `php _archive/php_utils/verify_database.php`

### Update Platform Status
1. Run: `python _archive/scrapers/scrape_platform_bulletproof.py`
2. Check page: http://127.0.0.1:8000/platforms

### Check Database Stats
```bash
php _archive/php_utils/verify_database.php
```

## File Organization

```
Root:
  Production files only (Laravel app, config, dependencies)

_archive/:
  /scrapers/      - All scraping scripts
  /logs/          - All log files
  /screenshots/   - Debug screenshots
  /test_files/    - Test scripts
  /temp_files/    - Temporary files
  /json_data/     - JSON data files
  /docs/          - Documentation
  /php_utils/     - PHP utilities
```

## Notes

- All scrapers use Playwright (headless Chrome)
- Items scraper takes ~5-10 minutes for all stores
- Platform scraper takes ~2-3 minutes
- Database has 7,374 records (items × platforms)
- Items page shows 2,484 unique items (grouped by shop + name)
- All times displayed in SGT (Singapore Time)
