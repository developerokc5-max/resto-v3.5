# Production Scraper Guide

## 🎯 What's New

✅ **Scans ALL 46 stores** (not just 3)
✅ **Extracts images** for `image_url` column
✅ **Saves full data**: name, SKU, category, price, availability
✅ **Skips unbound stores** (stores with no items/not accessible)
✅ **Saves to database** with change tracking

## 🚀 Quick Start

### Option 1: Run via Laravel (Recommended)

```bash
php artisan scrape:restosuite-production
```

This will:
- Show real-time progress
- Display summary when done
- Save results to database

### Option 2: Run Python directly

```bash
python scrape_restosuite_production.py
```

## 📊 What Gets Saved

The scraper saves to your `items` table:

| Column | Description | Example |
|--------|-------------|---------|
| `shop_id` | Store ID (1-46) | `1` |
| `shop_name` | Full store name | `OK CHICKEN RICE @ Toa Payoh` |
| `item_id` | RestoSuite item ID | `RS123456` |
| `name` | Item name | `Chicken Rice` |
| `sku` | Product SKU | `CHK-001` |
| `category` | Item category | `Main Dishes` |
| `price` | Price in dollars | `5.50` |
| `image_url` | Full image URL | `https://bo.sea.restosuite.ai/uploads/...` |
| `is_available` | Availability status | `1` (true) or `0` (false) |
| `platform` | Platform name | `restosuite` |

## 📈 Expected Results

Based on your current data:

- **Total stores**: ~46
- **Expected items**: ~7,000-8,000
- **Execution time**: ~5-10 minutes
- **Stores skipped**: Any unbound/inaccessible stores

## 🔍 How It Works

### 1. Login & Navigation
```
✓ Logs into RestoSuite
✓ Goes to product mapping page
✓ Opens store selector
```

### 2. Store Discovery
```
✓ Clicks "Stores" tab
✓ Scrolls through entire list
✓ Collects ALL store names (not just 3)
```

### 3. Item Extraction (Per Store)
```
✓ Selects store
✓ Waits for table to load
✓ Extracts from table:
  - Item name
  - Image URL (from <img> tag)
  - SKU
  - Category
  - Price (converts "$5.50" → 5.50)
  - Availability (from toggle state)
```

### 4. Database Saving
```
✓ Inserts new items
✓ Updates existing items
✓ Tracks availability changes in history
```

### 5. Skipping Logic
```
⏭️  Skips if: No table found
⏭️  Skips if: "No data" placeholder
⏭️  Skips if: Store not accessible
```

## 📋 View Results

After scraping, run the report:

```bash
php report_platform_items.php
```

This shows:
- Total items by platform
- Online/offline breakdown
- **Image coverage** (should be >0% after scraping!)
- Items per shop
- Recent changes

## 🎯 Difference from Old Scraper

| Feature | Old (3 stores) | New (Production) |
|---------|----------------|------------------|
| Stores scanned | 3 | ALL (46) |
| Images | ❌ No | ✅ Yes (image_url) |
| SKU | ❌ No | ✅ Yes |
| Category | ❌ No | ✅ Yes |
| Price | ❌ No | ✅ Yes (extracted) |
| Database | ❌ No | ✅ Yes (full save) |
| Skip unbound | ❌ No | ✅ Yes |
| Change tracking | ❌ No | ✅ Yes (history) |

## 🔧 Troubleshooting

### "Database connection failed"
```bash
# Check your .env file
DB_HOST=localhost
DB_DATABASE=resto_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### "No items found for store"
- Store may not be bound in RestoSuite
- These are automatically skipped
- Check summary for "stores_skipped"

### "Image URLs are empty"
- Some items may not have images in RestoSuite
- Scraper saves `NULL` for missing images
- Check image coverage in report

### Scraper is slow
- Normal! Processing 46 stores takes 5-10 minutes
- Each store needs page load + data extraction
- Browser automation is slower than API calls

## 📅 Automation

To run automatically, add to scheduler in `app/Console/Kernel.php`:

```php
// Run full RestoSuite scrape daily at 3 AM
$schedule->command('scrape:restosuite-production')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

## 🎉 Success Indicators

After scraping, you should see:

✅ **In terminal**:
```
Stores scraped: 40-46 (some may be skipped)
Total items: 7000+
Items inserted: XXX new items
Items updated: XXX existing items
Image coverage: >0% (was 0% before!)
```

✅ **In database**:
- `items` table has records with `platform = 'restosuite'`
- Many items have `image_url` populated
- Prices are numeric (5.50, not "$5.50")
- SKUs and categories filled

✅ **In report**:
```bash
php report_platform_items.php
```
Shows:
- RestroSuite platform with items
- Image coverage % improved
- Full item counts per shop

## 🆚 Compare Before/After

### Before Scraping
```bash
php report_platform_items.php
```
```
Platform: restosuite
Total Items: 0
Image Coverage: 0%
```

### After Scraping
```bash
php report_platform_items.php
```
```
Platform: restosuite
Total Items: 7500+
Image Coverage: 85%+ (depending on RestoSuite data)
Online: 6000+
Offline: 1500+
```

---

**Ready to run?**

```bash
php artisan scrape:restosuite-production
```

This will scan all stores and save everything to your database! 🚀
