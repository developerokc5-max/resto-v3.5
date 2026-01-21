# Project Structure

## Root Directory (Clean & Production Ready)

```
resto-db-v3/
├── _archive/               # All development files, tests, and historical data
├── app/                    # Laravel application code
├── bootstrap/              # Laravel bootstrap files
├── config/                 # Configuration files
├── database/              # Database files and migrations
├── docker/                # Docker configuration
├── node_modules/          # Node.js dependencies
├── public/                # Public web files
├── resources/             # Views, assets, and frontend code
├── routes/                # Application routes
├── scripts/               # Deployment scripts
├── storage/               # Laravel storage (logs, cache, uploads)
├── store_screenshots/     # Store screenshots from scraping
├── tests/                 # Application tests
├── vendor/                # PHP dependencies
│
├── .env                   # Environment configuration
├── .env.example           # Environment template
├── .gitignore            # Git ignore rules
├── artisan               # Laravel CLI tool
├── composer.json         # PHP dependencies
├── composer.lock         # PHP dependency lock file
├── docker-compose.yml    # Docker Compose configuration
├── Dockerfile            # Docker build file
├── package.json          # Node.js dependencies
├── package-lock.json     # Node.js dependency lock file
├── phpunit.xml           # PHPUnit configuration
├── render.yaml           # Render deployment config
├── render-build.sh       # Render build script
└── vite.config.js        # Vite build configuration
```

## Archive Directory Structure

```
_archive/
├── scrapers/              # All Python scraping scripts
│   ├── scrape_complete_final.py          # Main production scraper
│   ├── scrape_platform_bulletproof.py    # Platform status scraper
│   ├── scrape_items_*.py                 # Various item scrapers
│   ├── scrape_brands_*.py                # Brand-based scrapers
│   └── [other development versions]
│
├── logs/                  # All log files
│   ├── scraper_*.log                     # Scraper execution logs
│   ├── server_output.log                 # Server logs
│   └── [various test and debug logs]
│
├── screenshots/           # Screenshots from scraping
│   ├── debug_page_*.png
│   ├── step*_*.png
│   └── [other debug screenshots]
│
├── test_files/           # Test scripts and files
│   ├── test_*.py                         # Python test scripts
│   ├── test_*.php                        # PHP test scripts
│   ├── test_*.html                       # HTML test pages
│   └── debug_*.html                      # Debug HTML files
│
├── temp_files/           # Temporary files
│   ├── tmpclaude-*                       # Claude temp files
│   └── [other temporary files]
│
├── json_data/            # JSON data files
│   ├── items_complete.json               # Main production item data (2.8MB)
│   ├── items_*.json                      # Various item data files
│   ├── stores_list.json                  # Store list
│   └── bulletproof_test_results.json     # Test results
│
├── docs/                 # Project documentation
│   ├── README.md                         # Main README
│   ├── SETUP.md                          # Setup instructions
│   ├── DEPLOYMENT.md                     # Deployment guide
│   ├── ITEMS_PAGE_STATUS.md              # Items page status
│   ├── PLATFORM_MONITORING.md            # Platform monitoring docs
│   ├── SCRAPING_COMPLETE_SUMMARY.md      # Scraping summary
│   └── [other documentation files]
│
└── php_utils/            # PHP utility scripts
    ├── import_scraped_items.php          # Import JSON to database
    ├── verify_database.php               # Database verification
    ├── check_images.php                  # Image checking utility
    ├── check_table.php                   # Table checking utility
    └── [other utility scripts]
```

## Key Production Files

### Scrapers (in _archive/scrapers/)
- **scrape_complete_final.py** - Main scraper for all items (36 stores, 3 platforms)
- **scrape_platform_bulletproof.py** - Platform status checker

### Data Files (in _archive/json_data/)
- **items_complete.json** - 7,374 items across all platforms (production data)

### Import Scripts (in _archive/php_utils/)
- **import_scraped_items.php** - Import items from JSON to database
- **verify_database.php** - Verify database integrity and stats

### Documentation (in _archive/docs/)
- **ITEMS_PAGE_STATUS.md** - Current status of items page
- **DEPLOYMENT.md** - How to deploy the application
- **SETUP.md** - Setup instructions

## Database

Located in `database/database.sqlite` (not archived):
- **restosuite_item_snapshots** - Item snapshots from API
- **restosuite_item_changes** - Item change history
- **platform_status** - Platform online/offline status
- **items** - Menu items (7,374 records) for items page

## Web Routes

Located in `routes/web.php`:
- `/` - Dashboard (Overview)
- `/stores` - Stores list
- `/items` - Items page (2,484 unique items, paginated)
- `/platforms` - Platform status page
- `/item-tracking` - Item change history

## API Routes

Located in `routes/api.php`:
- `/api/sync/scrape` - Trigger platform scraping
- `/api/sync/scrape-items` - Trigger items scraping
- `/api/platform/status` - Get platform status
- `/api/items/list` - Get items list

## Notes

- All development files are safely archived in `_archive/`
- Root directory only contains production-ready files
- No code references were broken - all imports still work
- Archive is organized by file type for easy navigation
- README.md in `_archive/` explains the structure

## Restoring Archived Files

If you need to use any archived file:
1. Navigate to appropriate subfolder in `_archive/`
2. Copy (don't move) the file to where needed
3. For scrapers: `python _archive/scrapers/scrape_complete_final.py`
4. For imports: `php _archive/php_utils/import_scraped_items.php`
