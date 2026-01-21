# Archive Folder Structure

This folder contains all development files, test scripts, and documentation that are not actively used in production but kept for reference.

## Folder Organization

### `/scrapers`
All Python scraper scripts (development versions, test versions, deprecated versions)
- `scrape_complete_final.py` - Main production scraper
- `scrape_platform_bulletproof.py` - Platform status scraper
- Various test and development versions

### `/logs`
All log files from scraping operations, tests, and development
- Scraper output logs
- Test run logs
- Server logs
- Debug logs

### `/screenshots`
Screenshots captured during scraping/testing
- Debug page screenshots
- Step-by-step scraping process screenshots
- Store screenshots

### `/test_files`
Test scripts and temporary test files
- Python test scripts
- PHP test scripts
- HTML test pages
- Test output files

### `/temp_files`
Temporary files created by Claude and other tools
- `tmpclaude-*` files

### `/json_data`
JSON data files from scraping and testing
- `items_complete.json` - Main production data
- Test data files
- Store data files
- Intermediate scraping results

### `/docs`
Project documentation and status files
- Deployment guides
- Feature completion docs
- System summaries
- Setup instructions

### `/php_utils`
PHP utility scripts for data import and verification
- `import_scraped_items.php` - Import JSON data to database
- `verify_database.php` - Database verification script
- Image checking utilities
- Test utilities

## Important Files Still in Root

The following files remain in the project root because they're actively used:

- `.env`, `.env.example` - Environment configuration
- `composer.json`, `package.json` - Dependencies
- `docker-compose.yml`, `Dockerfile` - Docker configuration
- `artisan` - Laravel CLI
- `phpunit.xml` - Testing configuration
- `vite.config.js` - Frontend build config

## Restoring Files

If you need to use any archived file:
1. Locate it in the appropriate subfolder
2. Copy (don't move) it to where needed
3. All paths in the code reference the archived locations
