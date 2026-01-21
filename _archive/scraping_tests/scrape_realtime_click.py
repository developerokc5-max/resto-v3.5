#!/usr/bin/env python3
"""
REALTIME CLICK SCRAPER - For Run Sync Button
- Scans ALL stores from Item Mapping page
- Clicks Grab tab for each store
- Extracts images and all item data
- Saves to database (items table)
- Skips unbound/inaccessible stores
"""

import sys
import json
import re
from playwright.sync_api import sync_playwright
from datetime import datetime

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

def get_db_connection():
    """Get database connection from .env (supports SQLite and MySQL)"""
    import os
    import sqlite3
    from dotenv import load_dotenv

    load_dotenv()

    db_connection = os.getenv('DB_CONNECTION', 'sqlite')

    if db_connection == 'sqlite':
        # SQLite connection
        db_path = os.getenv('DB_DATABASE', 'database/database.sqlite')
        # Remove any drive letters or convert Windows paths
        if ':' in db_path:
            db_path = db_path  # Use as-is
        conn = sqlite3.connect(db_path)
        # Enable row factory for dict-like access
        conn.row_factory = sqlite3.Row
        return conn
    else:
        # MySQL connection
        import MySQLdb
        return MySQLdb.connect(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USERNAME', 'root'),
            passwd=os.getenv('DB_PASSWORD', ''),
            db=os.getenv('DB_DATABASE', 'resto_db'),
            charset='utf8mb4'
        )

def extract_price(price_text):
    """Extract numeric price from text like '$5.50' or 'S$5.50'"""
    if not price_text:
        return None

    match = re.search(r'[\d.]+', str(price_text))
    if match:
        try:
            return float(match.group())
        except:
            return None
    return None

def scrape_store_items(page, store_name):
    """Scrape items from a single store by clicking on left sidebar"""
    items = []

    try:
        # Click on store name in left sidebar
        try:
            # Look for the store in the left sidebar
            page.click(f"text={store_name}", timeout=5000)
            page.wait_for_timeout(2000)
            log(f"  ✓ Clicked on store in sidebar")
        except Exception as e:
            log(f"  ⚠ Could not find store in sidebar: {e}")
            return []

        # Click on Grab tab to see the items
        try:
            page.click("text=Grab", timeout=5000)
            page.wait_for_timeout(2000)
            log(f"  ✓ Switched to Grab tab")
        except Exception as e:
            log(f"  ⚠ Could not click Grab tab: {e}")

        # Wait for table to load
        try:
            page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
        except:
            log(f"  ⚠ No table found - store may not be bound")
            return []

        # Check if there's data
        placeholder = page.query_selector("table .ant-table-placeholder")
        if placeholder:
            log(f"  ⚠ Store has no items (not bound)")
            return []

        # Get all item rows
        rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")

        if len(rows) == 0:
            log(f"  ⚠ No items found")
            return []

        log(f"  ✓ Found {len(rows)} items, extracting data...")

        # Extract data from each row
        for row_idx, row in enumerate(rows, 1):
            try:
                # Get all cells in this row
                cells = row.query_selector_all("td")

                if len(cells) < 4:
                    continue

                # Extract item data from table columns
                # Typical RestoSuite table structure:
                # Column 0: Checkbox
                # Column 1: Item image + name
                # Column 2: SKU
                # Column 3: Category
                # Column 4: Price
                # Column 5: Availability toggle

                # Get item name (usually in first or second column)
                item_name = None
                for cell in cells[:3]:
                    text = cell.inner_text().strip()
                    if text and len(text) > 2 and text not in ['✓', '✗']:
                        item_name = text
                        break

                if not item_name:
                    continue

                # Get image URL
                image_url = None
                try:
                    img_elem = row.query_selector('img')
                    if img_elem:
                        image_url = img_elem.get_attribute('src')
                        # Handle relative URLs
                        if image_url and not image_url.startswith('http'):
                            image_url = BASE_URL + image_url if image_url.startswith('/') else None
                except:
                    pass

                # Get SKU (usually column 2 or 3)
                sku = None
                try:
                    sku_cell = cells[2] if len(cells) > 2 else None
                    if sku_cell:
                        sku_text = sku_cell.inner_text().strip()
                        if sku_text and sku_text != item_name:
                            sku = sku_text
                except:
                    pass

                # Get category
                category = None
                try:
                    cat_cell = cells[3] if len(cells) > 3 else None
                    if cat_cell:
                        category = cat_cell.inner_text().strip()
                except:
                    pass

                # Get price
                price = None
                try:
                    price_cell = cells[4] if len(cells) > 4 else None
                    if price_cell:
                        price_text = price_cell.inner_text().strip()
                        price = extract_price(price_text)
                except:
                    pass

                # Get availability (check for toggle or status)
                is_available = True  # Default to available
                try:
                    # Look for availability toggle/status
                    avail_cell = cells[5] if len(cells) > 5 else cells[-1]
                    avail_toggle = avail_cell.query_selector('[role="switch"], .ant-switch')
                    if avail_toggle:
                        # Check if toggle is on (has 'ant-switch-checked' class)
                        class_attr = avail_toggle.get_attribute('class') or ''
                        is_available = 'checked' in class_attr.lower()
                except:
                    pass

                # Get item_id if available
                item_id = None
                try:
                    # Some systems store item ID in data attributes
                    item_id_elem = row.query_selector('[data-item-id], [data-id]')
                    if item_id_elem:
                        item_id = item_id_elem.get_attribute('data-item-id') or item_id_elem.get_attribute('data-id')
                except:
                    pass

                items.append({
                    'name': item_name,
                    'sku': sku,
                    'category': category,
                    'price': price,
                    'image_url': image_url,
                    'is_available': is_available,
                    'item_id': item_id,
                })

            except Exception as e:
                log(f"    Error parsing row {row_idx}: {e}")
                continue

        log(f"  ✓ Extracted {len(items)} items with data")

    except Exception as e:
        log(f"  ✗ Error scraping store: {e}")

    return items

def save_items_to_db(shop_id, shop_name, items, conn):
    """Save items to database"""
    cursor = conn.cursor()

    items_inserted = 0
    items_updated = 0
    history_inserted = 0

    for item in items:
        try:
            # Check if item already exists
            cursor.execute("""
                SELECT id, is_available, image_url FROM items
                WHERE shop_id = ? AND platform = 'restosuite' AND name = ?
                LIMIT 1
            """, (shop_id, item['name']))

            existing = cursor.fetchone()

            if existing:
                # Update existing item
                cursor.execute("""
                    UPDATE items SET
                        sku = ?,
                        category = ?,
                        price = ?,
                        image_url = ?,
                        is_available = ?,
                        item_id = ?,
                        updated_at = datetime('now')
                    WHERE shop_id = ? AND platform = 'restosuite' AND name = ?
                """, (
                    item['sku'],
                    item['category'],
                    item['price'],
                    item['image_url'],
                    item['is_available'],
                    item['item_id'],
                    shop_id,
                    item['name']
                ))
                items_updated += 1

                # Check if availability changed
                if existing[1] != item['is_available']:
                    # Insert history record
                    cursor.execute("""
                        INSERT INTO item_status_history
                        (item_name, shop_id, shop_name, platform, is_available, price, category, image_url, changed_at, created_at, updated_at)
                        VALUES (?, ?, ?, 'restosuite', ?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))
                    """, (
                        item['name'],
                        shop_id,
                        shop_name,
                        item['is_available'],
                        item['price'],
                        item['category'],
                        item['image_url']
                    ))
                    history_inserted += 1

            else:
                # Insert new item
                cursor.execute("""
                    INSERT INTO items
                    (shop_id, shop_name, item_id, name, sku, category, price, image_url, is_available, platform, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'restosuite', datetime('now'), datetime('now'))
                """, (
                    shop_id,
                    shop_name,
                    item['item_id'],
                    item['name'],
                    item['sku'],
                    item['category'],
                    item['price'],
                    item['image_url'],
                    item['is_available']
                ))
                items_inserted += 1

                # Insert initial history record
                cursor.execute("""
                    INSERT INTO item_status_history
                    (item_name, shop_id, shop_name, platform, is_available, price, category, image_url, changed_at, created_at, updated_at)
                    VALUES (?, ?, ?, 'restosuite', ?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))
                """, (
                    item['name'],
                    shop_id,
                    shop_name,
                    item['is_available'],
                    item['price'],
                    item['category'],
                    item['image_url']
                ))
                history_inserted += 1

        except Exception as e:
            log(f"    Error saving item '{item['name']}': {e}")

    conn.commit()

    return {
        'inserted': items_inserted,
        'updated': items_updated,
        'history': history_inserted
    }

def main():
    log("="*70)
    log("REALTIME CLICK SCRAPER")
    log("Scanning ALL stores with full data extraction")
    log("="*70)

    # Connect to database
    try:
        conn = get_db_connection()
        log("✓ Database connected\n")
    except Exception as e:
        log(f"✗ Database connection failed: {e}")
        return 1

    result = {
        "total_stores_found": 0,
        "stores_scraped": 0,
        "stores_skipped": 0,
        "total_items": 0,
        "items_inserted": 0,
        "items_updated": 0,
        "history_records": 0,
        "errors": []
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("Step 1: Logging in...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(3000)

            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')

            page.wait_for_selector("#password", timeout=10000)
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')

            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(3000)
            log("✓ Login successful\n")

            # Navigate to Takeaway Product Mapping
            log("Step 2: Navigating to Takeaway Product Mapping...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded\n")

            # Get all stores from left sidebar
            log("Step 3: Getting stores from left sidebar...")

            # Find all store groups and expand them
            stores = []

            # Look for expandable store groups in sidebar (like Le Le Mee Pok, HUMFULL, etc.)
            store_groups = page.query_selector_all("nav a, nav button, nav div[role='button']")

            for group in store_groups:
                try:
                    text = group.inner_text().strip()
                    # Filter out navigation items, only keep actual store names
                    if text and len(text) > 3 and text not in ["Home", "Items", "Restaurant", "Online", "Takeout & delivery", "Financial", "Message", "Alert Center", "Basic Services"]:
                        if "@" in text or any(brand in text for brand in ["Le Le Mee Pok", "HUMFULL", "OK CHICKEN RICE", "JKT Western", "Drinks", "AH HUAT"]):
                            stores.append(text)
                except:
                    continue

            # Remove duplicates while preserving order
            seen = set()
            unique_stores = []
            for store in stores:
                if store not in seen:
                    seen.add(store)
                    unique_stores.append(store)

            stores = unique_stores
            result["total_stores_found"] = len(stores)
            log(f"✓ Found {len(stores)} stores in sidebar\n")

            # Scrape ALL stores
            log("="*70)
            log(f"Step 4: Scraping ALL {len(stores)} stores...")
            log("="*70)

            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")

                try:
                    # Generate shop_id from store name (you may want to adjust this)
                    shop_id = str(idx)  # Or extract from store name

                    # Scrape items
                    items = scrape_store_items(page, store_name)

                    if len(items) == 0:
                        result["stores_skipped"] += 1
                        continue

                    # Save to database
                    log(f"  💾 Saving to database...")
                    stats = save_items_to_db(shop_id, store_name, items, conn)

                    result["stores_scraped"] += 1
                    result["total_items"] += len(items)
                    result["items_inserted"] += stats['inserted']
                    result["items_updated"] += stats['updated']
                    result["history_records"] += stats['history']

                    log(f"  ✓ Saved: {stats['inserted']} new, {stats['updated']} updated, {stats['history']} history")

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    result["errors"].append({"store": store_name, "error": str(e)})
                    result["stores_skipped"] += 1

                # Small delay between stores
                page.wait_for_timeout(1000)

        except Exception as e:
            log(f"\n✗ Fatal Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            browser.close()

    conn.close()

    # Print summary
    log("\n" + "="*70)
    log("SCRAPING COMPLETE!")
    log("="*70)
    log(f"Total stores found:    {result['total_stores_found']}")
    log(f"Stores scraped:        {result['stores_scraped']}")
    log(f"Stores skipped:        {result['stores_skipped']}")
    log(f"Total items:           {result['total_items']}")
    log(f"Items inserted:        {result['items_inserted']}")
    log(f"Items updated:         {result['items_updated']}")
    log(f"History records:       {result['history_records']}")
    log(f"Errors:                {len(result['errors'])}")
    log("="*70)

    # Save results
    with open("scrape_results.json", "w") as f:
        json.dump(result, f, indent=2)

    log("\n✓ Results saved to scrape_results.json")

    return 0

if __name__ == "__main__":
    sys.exit(main())
