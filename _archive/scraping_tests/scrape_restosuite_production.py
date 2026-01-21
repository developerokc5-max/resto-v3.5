#!/usr/bin/env python3
"""
PRODUCTION RestoSuite Scraper
- Scans ALL stores (not just 3)
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

def scrape_store_items(page, store_name, selector):
    """Scrape items from a single store"""
    items = []

    try:
        # Open dropdown
        page.click(selector, timeout=5000)
        page.wait_for_timeout(1000)

        # Click Stores tab (bulletproof)
        try:
            page.click("text=Stores", timeout=3000)
            page.wait_for_timeout(500)
        except:
            pass

        # Type store name
        page.keyboard.type(store_name, delay=50)
        page.wait_for_timeout(1500)

        # Press Enter to select
        page.keyboard.press("Enter")
        page.wait_for_timeout(4000)

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

def get_shop_id_from_name(shop_name, conn):
    """Get shop_id from platform_status table based on store name"""
    cursor = conn.cursor()

    # Try exact match first
    cursor.execute("""
        SELECT shop_id FROM platform_status
        WHERE store_name = ?
        LIMIT 1
    """, (shop_name,))

    result = cursor.fetchone()
    if result:
        return result[0]

    # Try fuzzy match (in case of slight name differences)
    cursor.execute("""
        SELECT shop_id, store_name FROM platform_status
        WHERE store_name LIKE ?
        LIMIT 1
    """, (f"%{shop_name}%",))

    result = cursor.fetchone()
    if result:
        log(f"    Matched '{shop_name}' to '{result[1]}' (shop_id: {result[0]})")
        return result[0]

    # No match found - return None
    log(f"    ⚠ No shop_id found for '{shop_name}' - skipping")
    return None

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
    log("PRODUCTION RESTOSUITE SCRAPER")
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

            # Open dropdown and get all stores
            log("Step 3: Getting all stores...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)

            # Ensure on Stores tab
            try:
                page.click("text=Stores", timeout=3000)
                page.wait_for_timeout(1000)
            except:
                pass

            # Scroll and collect all stores
            stores = []
            seen = set()

            for scroll_attempt in range(30):
                all_text_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span, .ant-dropdown button")

                count_this_round = 0
                for elem in all_text_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Accept store names that have @ symbol OR are longer than 10 chars with spaces
                        if text and (("@" in text) or (len(text) > 10 and " " in text)):
                            # Only exclude tab labels, include ALL stores (including testing/office)
                            if text not in ["Group", "Brands", "Stores", "Organizations"]:
                                if text not in seen:
                                    stores.append(text)
                                    seen.add(text)
                                    count_this_round += 1
                                    log(f"  Found store: {text}")
                    except:
                        continue

                if len(stores) > 0 and count_this_round == 0 and scroll_attempt > 5:
                    break

                try:
                    page.evaluate("""
                        const dropdowns = document.querySelectorAll('.ant-dropdown, .rc-virtual-list-holder, [class*="overflow"]');
                        dropdowns.forEach(d => {
                            if (d.scrollHeight > d.clientHeight) {
                                d.scrollTop += 300;
                            }
                        });
                    """)
                    page.wait_for_timeout(300)
                except:
                    pass

            result["total_stores_found"] = len(stores)
            log(f"✓ Found {len(stores)} stores\n")

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Scrape ALL stores
            log("="*70)
            log(f"Step 4: Scraping ALL {len(stores)} stores...")
            log("="*70)

            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")

                try:
                    # Get shop_id from database
                    shop_id = get_shop_id_from_name(store_name, conn)

                    if shop_id is None:
                        log(f"  ⚠ Store not found in database - skipping")
                        result["stores_skipped"] += 1
                        continue

                    # Scrape items
                    items = scrape_store_items(page, store_name, selector)

                    if len(items) == 0:
                        result["stores_skipped"] += 1
                        continue

                    # Save to database
                    log(f"  💾 Saving to database (shop_id: {shop_id})...")
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
