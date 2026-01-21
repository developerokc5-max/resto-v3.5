# Production Web Scraper - Real Platform Data

This system uses **real browser automation** to scrape actual data from delivery platforms instead of relying on APIs.

## Features

✅ **Real Images** - Downloads actual product images from platforms
✅ **Real Prices** - Gets current prices directly from platform pages
✅ **Real Availability** - Detects sold-out items and availability status
✅ **Multi-Platform** - Supports Grab, FoodPanda, and Deliveroo
✅ **Production Ready** - Headless browser mode for automated scheduling

## Setup

### 1. Install Python Dependencies

```bash
pip install playwright python-dotenv mysqlclient
playwright install chromium
```

### 2. Test the Scraper

First, test with sample URLs to ensure it works:

```bash
python test_platform_scraper.py
```

This will:
- Open a browser window
- Navigate to test stores
- Extract menu items with images/prices
- Save screenshots and HTML for debugging

### 3. Update Test URLs

Edit `test_platform_scraper.py` and replace the sample URLs with your actual store URLs:

```python
# Grab URL format
test_url = "https://food.grab.com/sg/en/restaurant/YOUR-STORE-NAME/YOUR-STORE-ID"

# FoodPanda URL format
test_url = "https://www.foodpanda.sg/restaurant/YOUR-STORE-SLUG"

# Deliveroo URL format
test_url = "https://deliveroo.com.sg/menu/singapore/YOUR-STORE-SLUG"
```

## Usage

### Manual Testing

Test with visible browser (useful for debugging):

```bash
# Scrape Grab stores
python scrape_platforms.py --platform grab --limit 3

# Scrape FoodPanda stores
python scrape_platforms.py --platform foodpanda --limit 3

# Scrape all platforms
python scrape_platforms.py --platform all --limit 5
```

### Production Mode (Headless)

```bash
# Run in headless mode for automation
python scrape_platforms.py --platform all --limit 10 --headless

# Scrape specific shop
python scrape_platforms.py --shop-id YOUR_SHOP_ID --headless
```

### Laravel Integration

Run via Laravel command:

```bash
# Scrape all platforms
php artisan scrape:platforms --platform=all --limit=5 --headless

# Scrape specific platform
php artisan scrape:platforms --platform=grab --limit=10

# Scrape specific shop
php artisan scrape:platforms --shop-id=123 --headless
```

## Automated Scheduling

The scraper is **automatically scheduled** to run every 30 minutes:

```php
// In app/Console/Kernel.php
$schedule->command('scrape:platforms --platform=all --limit=5 --headless')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

To enable scheduling:

```bash
# Add this to your cron (Linux/Mac) or Task Scheduler (Windows)
* * * * * php /path/to/resto-db-v3.5/artisan schedule:run >> /dev/null 2>&1
```

## How It Works

### 1. Browser Automation
- Uses Playwright to launch a real Chromium browser
- Navigates to actual platform store pages
- Waits for JavaScript to load menu items

### 2. Data Extraction
- Finds menu items using CSS selectors
- Extracts item names, prices, images
- Detects sold-out/unavailable items

### 3. Database Updates
- Inserts/updates items in `items` table
- Tracks availability changes in `item_status_history`
- Stores image URLs for display

### 4. Rate Limiting
- 2-second delay between stores
- Headless mode for efficiency
- Graceful error handling

## Customizing Selectors

If platform HTML structure changes, update selectors in `scrape_platforms.py`:

```python
# Grab selectors
menu_items = page.query_selector_all('[class*="MenuItem"]')

# FoodPanda selectors
menu_items = page.query_selector_all('[data-testid*="menu-product"]')

# Deliveroo selectors
menu_items = page.query_selector_all('[data-testid*="menu-item"]')
```

## Troubleshooting

### Browser doesn't launch
```bash
# Reinstall browsers
playwright install chromium
```

### Database connection fails
Check your `.env` file has correct credentials:
```
DB_HOST=localhost
DB_DATABASE=resto_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### No items found
1. Run test script WITHOUT `--headless` to see browser
2. Check screenshots saved by test script
3. Update CSS selectors if platform changed

### Scraper is slow
- Use `--limit` to scrape fewer shops per run
- Run more frequently with smaller batches
- Ensure good internet connection

## Production Checklist

- [ ] Tested scraper with your actual store URLs
- [ ] Updated selectors if needed
- [ ] Database credentials configured in `.env`
- [ ] Chromium browser installed (`playwright install chromium`)
- [ ] Scheduled task configured for `schedule:run`
- [ ] Logs monitored (`storage/logs/laravel.log`)

## Performance

- **Speed**: ~10-15 seconds per store (including page load)
- **Frequency**: Every 30 minutes recommended
- **Batch size**: 5-10 stores per run for reliability
- **Resource usage**: ~200MB RAM per browser instance

## Comparison: API vs Web Scraping

| Feature | API (Old) | Web Scraping (New) |
|---------|-----------|-------------------|
| Images | ❌ Placeholder | ✅ Real images |
| Prices | ❌ May be missing | ✅ Current prices |
| Availability | ✅ Yes | ✅ Yes + visual confirmation |
| Speed | ⚡ Fast (1s/store) | 🐢 Slower (10s/store) |
| Reliability | ⚠️ API changes | ⚠️ HTML changes |
| Rate Limits | ✅ Low risk | ⚠️ Need delays |

## Next Steps

1. **Test First**: Run `test_platform_scraper.py` to verify
2. **Update URLs**: Add your actual store URLs
3. **Run Manual Test**: Try `scrape:platforms` command
4. **Enable Scheduling**: Set up cron for automation
5. **Monitor Logs**: Check for errors and adjust

---

**Note**: Web scraping should respect platform Terms of Service. Only scrape your own stores or with permission. Add appropriate delays and respect robots.txt.
