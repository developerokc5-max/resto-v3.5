#!/usr/bin/env python3
"""
BULLETPROOF RestoSuite Scraper - Scrapes ALL stores without crashing
- Refreshes page between stores to prevent memory buildup
- Saves progress after each store
- Can resume from last successful store
- Handles all ~50 stores reliably
"""

import sys
import json
import re
import os
from playwright.sync_api import sync_playwright
from datetime import datetime

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"
PROGRESS_FILE = "scrape_progress_all_stores.json"

def log(msg):
    print(msg, file=sys.stderr)

def get_db_connection():
    """Get database connection from .env (supports SQLite and MySQL)"""
    import sqlite3
    from dotenv import load_dotenv

    load_dotenv()

    db_connection = os.getenv('DB_CONNECTION', 'sqlite')

    if db_connection == 'sqlite':
        db_path = os.getenv('DB_DATABASE', 'database/database.sqlite')
        conn = sqlite3.connect(db_path)
        conn.row_factory = sqlite3.Row
        return conn
    else:
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

    # Try fuzzy match
    cursor.execute("""
        SELECT shop_id, store_name FROM platform_status
        WHERE store_name LIKE ?
        LIMIT 1
    """, (f"%{shop_name}%",))

    result = cursor.fetchone()
    if result:
        log(f"    Matched '{shop_name}' to '{result[1]}' (shop_id: {result[0]})")
        return result[0]

    return None

def login_and_get_stores(page):
    """Login and get list of all stores"""
    log("Logging in...")
    page.goto(f"{BASE_URL}/login", wait_until="networkidle")
    page.wait_for_timeout(2000)

    page.wait_for_selector("#username", timeout=30000)
    page.fill("#username", EMAIL)
    page.click('button[type="submit"]')

    page.wait_for_selector("#password", timeout=10000)
    page.fill("#password", PASSWORD)
    page.click('button[type="submit"]')

    page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
    page.wait_for_timeout(2000)
    log("✓ Login successful\n")

    # Navigate to Takeaway Product Mapping
    log("Navigating to Takeaway Product Mapping...")
    page.goto(f"{BASE_URL}/takeaway-product-mapping")
    page.wait_for_timeout(5000)
    log("✓ Page loaded\n")

    # Get all stores from dropdown
    log("Collecting all store names...")
    selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
    page.click(selector)
    page.wait_for_timeout(2000)

    # Click Stores tab
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
                if text and (("@" in text) or (len(text) > 10 and " " in text)):
                    if text not in ["Group", "Brands", "Stores", "Organizations"]:
                        if text not in seen:
                            stores.append(text)
                            seen.add(text)
                            count_this_round += 1
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

    # Close dropdown
    page.keyboard.press("Escape")
    page.wait_for_timeout(1000)

    log(f"✓ Found {len(stores)} stores\n")
    return stores

def scrape_single_store(page, store_name):
    """Scrape items from a single store - opens fresh page each time"""
    items = []

    try:
        # Navigate to the page (fresh start)
        log(f"  Loading Takeaway Product Mapping page...")
        page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
        page.wait_for_timeout(3000)

        # Open dropdown
        selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
        page.click(selector, timeout=10000)
        page.wait_for_timeout(1500)

        # Click Stores tab
        try:
            page.click("text=Stores", timeout=3000)
            page.wait_for_timeout(500)
        except:
            pass

        # Type store name
        log(f"  Selecting store: {store_name}")
        page.keyboard.type(store_name, delay=50)
        page.wait_for_timeout(2000)

        # Click on the store from dropdown instead of pressing Enter
        try:
            # Find and click the store in the dropdown list
            store_option = page.locator(f"text={store_name}").first
            store_option.click(timeout=5000)
            page.wait_for_timeout(3000)
            log(f"  ✓ Clicked on store option")
        except:
            # Fallback to Enter key
            page.keyboard.press("Enter")
            page.wait_for_timeout(4000)
            log(f"  ✓ Pressed Enter to select")

        # Verify store was selected by checking if dropdown shows the store name
        try:
            selected_store = page.locator(selector).inner_text(timeout=3000)
            if store_name not in selected_store:
                log(f"  ⚠ Store selection may have failed - showing: {selected_store[:50]}")
                return []
            log(f"  ✓ Store selected: {selected_store[:50]}")
        except:
            log(f"  ⚠ Could not verify store selection")
            pass

        # Click Grab tab
        try:
            page.click("text=Grab", timeout=5000)
            page.wait_for_timeout(2000)
            log(f"  ✓ Switched to Grab tab")
        except Exception as e:
            log(f"  ⚠ Could not click Grab tab: {e}")

        # Wait for table
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
                # Try to get item name from the row's text content
                # Method 1: Look for item name in specific div/span elements
                item_name_elem = row.query_selector('td [class*="item"], td [class*="name"], td div, td span')
                item_name = None

                if item_name_elem:
                    item_name = item_name_elem.inner_text().strip()

                # Method 2: If not found, get all text from cells
                if not item_name or len(item_name) < 3:
                    cells = row.query_selector_all("td")
                    for cell in cells:
                        text = cell.inner_text().strip()
                        # Skip empty, very short, checkboxes, numbers, prices
                        if text and len(text) > 3:
                            # Skip if it's just a number or price
                            clean_text = text.replace('$', '').replace('S$', '').replace(',', '').replace('.', '').strip()
                            if clean_text and not clean_text.isdigit():
                                # This looks like text (item name), not a number
                                item_name = text
                                break

                if not item_name or len(item_name) < 3:
                    if row_idx <= 3:  # Only log for first few rows
                        log(f"    Row {row_idx}: Could not find item name")
                    continue

                # Get image URL
                image_url = None
                try:
                    img_elem = row.query_selector('img')
                    if img_elem:
                        image_url = img_elem.get_attribute('src')
                        if image_url and not image_url.startswith('http'):
                            image_url = BASE_URL + image_url if image_url.startswith('/') else None
                except:
                    pass

                # Get SKU
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

                # Get availability
                is_available = True
                try:
                    avail_cell = cells[5] if len(cells) > 5 else cells[-1]
                    avail_toggle = avail_cell.query_selector('[role="switch"], .ant-switch')
                    if avail_toggle:
                        class_attr = avail_toggle.get_attribute('class') or ''
                        is_available = 'checked' in class_attr.lower()
                except:
                    pass

                items.append({
                    'name': item_name,
                    'sku': sku,
                    'category': category,
                    'price': price,
                    'image_url': image_url,
                    'is_available': is_available,
                })

            except Exception as e:
                log(f"    Error parsing row {row_idx}: {e}")
                continue

        log(f"  ✓ Extracted {len(items)} items")

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
                        updated_at = datetime('now')
                    WHERE shop_id = ? AND platform = 'restosuite' AND name = ?
                """, (
                    item['sku'],
                    item['category'],
                    item['price'],
                    item['image_url'],
                    item['is_available'],
                    shop_id,
                    item['name']
                ))
                items_updated += 1

                # Check if availability changed
                if existing[1] != item['is_available']:
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
                    (shop_id, shop_name, name, sku, category, price, image_url, is_available, platform, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'restosuite', datetime('now'), datetime('now'))
                """, (
                    shop_id,
                    shop_name,
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

def save_progress(progress):
    """Save progress to file"""
    with open(PROGRESS_FILE, 'w') as f:
        json.dump(progress, f, indent=2)

def load_progress():
    """Load progress from file"""
    if os.path.exists(PROGRESS_FILE):
        with open(PROGRESS_FILE, 'r') as f:
            return json.load(f)
    return {
        "completed_stores": [],
        "last_index": -1
    }

def main():
    log("="*70)
    log("BULLETPROOF RESTOSUITE SCRAPER - ALL STORES")
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

    # Load progress
    progress = load_progress()
    log(f"Progress: Already completed {len(progress['completed_stores'])} stores\n")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login and get all stores
            stores = login_and_get_stores(page)
            result["total_stores_found"] = len(stores)

            # Filter out already completed stores
            remaining_stores = []
            for idx, store_name in enumerate(stores):
                if store_name not in progress['completed_stores']:
                    remaining_stores.append((idx, store_name))

            log(f"Remaining stores to scrape: {len(remaining_stores)}")
            log("="*70)

            # Scrape each store
            for list_idx, (original_idx, store_name) in enumerate(remaining_stores, 1):
                log(f"\n[{list_idx}/{len(remaining_stores)}] {store_name}")

                try:
                    # Get shop_id from database
                    shop_id = get_shop_id_from_name(store_name, conn)

                    if shop_id is None:
                        log(f"  ⚠ Store not found in database - skipping")
                        result["stores_skipped"] += 1
                        progress['completed_stores'].append(store_name)
                        save_progress(progress)
                        continue

                    # Scrape items from this store
                    items = scrape_single_store(page, store_name)

                    if len(items) == 0:
                        log(f"  ⚠ No items found - marking as complete")
                        result["stores_skipped"] += 1
                        progress['completed_stores'].append(store_name)
                        save_progress(progress)
                        continue

                    # Save to database
                    log(f"  💾 Saving {len(items)} items to database (shop_id: {shop_id})...")
                    stats = save_items_to_db(shop_id, store_name, items, conn)

                    result["stores_scraped"] += 1
                    result["total_items"] += len(items)
                    result["items_inserted"] += stats['inserted']
                    result["items_updated"] += stats['updated']
                    result["history_records"] += stats['history']

                    log(f"  ✓ Saved: {stats['inserted']} new, {stats['updated']} updated, {stats['history']} history")

                    # Mark as completed
                    progress['completed_stores'].append(store_name)
                    progress['last_index'] = original_idx
                    save_progress(progress)

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    result["errors"].append({"store": store_name, "error": str(e)})
                    # Don't mark as completed - will retry next time

                # Small delay between stores
                page.wait_for_timeout(2000)

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
    with open("scrape_all_stores_results.json", "w") as f:
        json.dump(result, f, indent=2)

    log("\n✓ Results saved to scrape_all_stores_results.json")
    log(f"✓ Progress saved to {PROGRESS_FILE}")

    return 0

if __name__ == "__main__":
    sys.exit(main())
