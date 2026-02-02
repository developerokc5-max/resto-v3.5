#!/usr/bin/env python3
"""
scrape_items_sync_v2.py - UPGRADED VERSION (Parallel by OUTLET + Timestamp-based)

IMPROVEMENTS OVER ORIGINAL:
1. PARALLEL BY OUTLET - Multiple browser instances, each handling different outlets
2. TIMESTAMP-BASED TRACKING - No data deletion, tracks changes over time
3. CHANGE DETECTION - Records when items go online/offline in item_status_history
4. FASTER - Cuts scrape time significantly by parallelizing outlet processing
5. SAFER - Database always has data, no gaps during scraping

APPROACH:
- Original: 1 browser → 46 outlets × 3 platforms = 138 sequential operations
- V2: 3 browsers → ~15 outlets each × 3 platforms = 46 parallel outlet operations

ORIGINAL FILE: scrape_items_sync.py (UNTOUCHED - still works as before)

Usage:
    python scrape_items_sync_v2.py

Author: Upgraded version
"""

import sys
import os
import sqlite3
import hashlib
from concurrent.futures import ThreadPoolExecutor, as_completed
from playwright.sync_api import sync_playwright
from datetime import datetime
from threading import Lock

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Database path - SQLite (using v3.5 database)
DB_PATH = os.getenv('DB_DATABASE', r'C:\resto-db-v3.5\database\database.sqlite')

# Log file path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(SCRIPT_DIR, "scrape_items_sync_v2.log")

# Number of parallel browsers (6 workers = ~20 min, uses ~6GB RAM)
NUM_WORKERS = 6

# Thread-safe logging
log_lock = Lock()

# Database lock for thread-safe writes
db_lock = Lock()


def log(msg, worker_id=None):
    """Thread-safe logging to both console and file"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    prefix = f"[Worker-{worker_id}]" if worker_id is not None else "[MAIN]"
    log_msg = f"[{timestamp}] {prefix} {msg}"

    with log_lock:
        print(log_msg, file=sys.stderr)
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(log_msg + "\n")


def get_db_connection():
    """Get a new database connection (thread-safe)"""
    conn = sqlite3.connect(DB_PATH, timeout=30.0)
    conn.row_factory = sqlite3.Row
    return conn


def save_items_with_history(items, worker_id):
    """
    Save items to database with change tracking.
    - Updates existing items
    - Inserts new items
    - Records status changes in item_status_history
    """
    if not items:
        return 0, 0, 0

    conn = get_db_connection()
    cursor = conn.cursor()

    inserted = 0
    updated = 0
    changes_recorded = 0

    try:
        with db_lock:
            for item in items:
                shop_name = item['shop_name']
                name = item['name']
                sku = item.get('sku', '')
                platform = item['platform']

                # Check if item exists
                cursor.execute("""
                    SELECT id, is_available, price, category, image_url
                    FROM items
                    WHERE shop_name = ? AND name = ? AND platform = ? AND (sku = ? OR (sku IS NULL AND ? = ''))
                """, (shop_name, name, platform, sku, sku))

                existing = cursor.fetchone()

                now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

                if existing:
                    # Item exists - check for changes
                    old_available = bool(existing[1])
                    new_available = bool(item['is_available'])

                    # Record status change if availability changed
                    if old_available != new_available:
                        cursor.execute("""
                            INSERT INTO item_status_history
                            (item_name, shop_id, shop_name, platform, is_available, price, category, image_url, changed_at, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        """, (
                            name,
                            item['shop_id'],
                            shop_name,
                            platform,
                            1 if new_available else 0,
                            item['price'],
                            item['category'],
                            item['image_url'],
                            now,
                            now,
                            now
                        ))
                        changes_recorded += 1
                        log(f"  STATUS CHANGE: {name} @ {shop_name} [{platform}] -> {'ONLINE' if new_available else 'OFFLINE'}", worker_id)

                    # Update existing item
                    cursor.execute("""
                        UPDATE items
                        SET is_available = ?, price = ?, category = ?, image_url = ?, updated_at = ?
                        WHERE id = ?
                    """, (
                        1 if item['is_available'] else 0,
                        item['price'],
                        item['category'],
                        item['image_url'],
                        now,
                        existing[0]
                    ))
                    updated += 1

                else:
                    # New item - insert it
                    cursor.execute("""
                        INSERT INTO items
                        (shop_id, shop_name, name, sku, category, price, image_url, is_available, platform, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        item['shop_id'],
                        shop_name,
                        name,
                        sku,
                        item['category'],
                        item['price'],
                        item['image_url'],
                        1 if item['is_available'] else 0,
                        platform,
                        now,
                        now
                    ))
                    inserted += 1

                    # Also record initial status in history (only for new items, skip if exists)
                    cursor.execute("""
                        INSERT INTO item_status_history
                        (item_name, shop_id, shop_name, platform, is_available, price, category, image_url, changed_at, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        name,
                        item['shop_id'],
                        shop_name,
                        platform,
                        1 if item['is_available'] else 0,
                        item['price'],
                        item['category'],
                        item['image_url'],
                        now,
                        now,
                        now
                    ))

            conn.commit()

    except Exception as e:
        log(f"ERROR saving items: {str(e)}", worker_id)
        conn.rollback()
    finally:
        conn.close()

    return inserted, updated, changes_recorded


def save_shop(shop_info, worker_id=None):
    """Save or update shop info"""
    conn = get_db_connection()
    cursor = conn.cursor()

    try:
        with db_lock:
            now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

            # Check if shop exists
            cursor.execute("SELECT id FROM shops WHERE shop_id = ?", (shop_info['shop_id'],))
            existing = cursor.fetchone()

            if existing:
                # Update
                cursor.execute("""
                    UPDATE shops
                    SET shop_name = ?, organization_name = ?, has_items = ?, last_synced_at = ?, updated_at = ?
                    WHERE shop_id = ?
                """, (
                    shop_info['shop_name'],
                    shop_info['organization_name'],
                    1 if shop_info['has_items'] else 0,
                    now,
                    now,
                    shop_info['shop_id']
                ))
            else:
                # Insert
                cursor.execute("""
                    INSERT INTO shops (shop_id, shop_name, organization_name, has_items, last_synced_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                """, (
                    shop_info['shop_id'],
                    shop_info['shop_name'],
                    shop_info['organization_name'],
                    1 if shop_info['has_items'] else 0,
                    now,
                    now,
                    now
                ))

            conn.commit()
    except Exception as e:
        log(f"ERROR saving shop: {str(e)}", worker_id)
        conn.rollback()
    finally:
        conn.close()


# List of brands with expected outlet counts
BRANDS = [
    ("Le Le Mee Pok", 2),
    ("JKT Western", 2),
    ("Drinks Stall", 2),
    ("HUMFULL", 18),
    ("OK Chicken Rice", 18),
    ("AH Huat Hokkien Mee", 4)
]


def extract_items_from_table(page, shop_name, platform_name, worker_id):
    """Extract all items from current platform's table"""
    items = []

    try:
        page.wait_for_timeout(1500)
        rows = page.locator('.ant-table-tbody tr').all()

        log(f"  [{platform_name}] Extracting from {len(rows)} rows...", worker_id)

        for row_idx, row in enumerate(rows):
            try:
                cells = row.locator('td').all()
                if len(cells) < 5:
                    continue

                # Column mapping (same as original)
                img = cells[2].locator('img').first if len(cells) > 2 else None
                image_url = ""
                if img and img.count() > 0:
                    image_url = img.get_attribute('src') or ""

                name_cell = cells[3] if len(cells) > 3 else None
                name = name_cell.text_content().strip() if name_cell else "Unknown"

                # Skip header rows (case-insensitive check)
                if name.lower() in ['item name', 'unknown', ''] or name.lower().startswith('item name'):
                    continue

                brand_cell = cells[4] if len(cells) > 4 else None
                brand = brand_cell.text_content().strip() if brand_cell else ""

                category_cell = cells[6] if len(cells) > 6 else None
                category = category_cell.text_content().strip() if category_cell else ""

                # Skip if category is a header value
                if category.lower() == 'menu group':
                    continue

                sku_cell = cells[7] if len(cells) > 7 else None
                sku = sku_cell.text_content().strip() if sku_cell else ""

                price_cell = cells[8] if len(cells) > 8 else None
                price_text = price_cell.text_content().strip() if price_cell else "0"
                price = 0.0
                try:
                    price = float(price_text.replace('S$', '').replace('$', '').replace(',', '').strip())
                except:
                    pass

                # Toggle switch (Column 17)
                toggle_cell = cells[17] if len(cells) > 17 else None
                is_available = False
                if toggle_cell:
                    toggle = toggle_cell.locator('.ant-switch').first
                    if toggle.count() > 0:
                        toggle_class = toggle.get_attribute('class')
                        is_available = 'ant-switch-checked' in toggle_class

                items.append({
                    'shop_id': shop_name,
                    'shop_name': shop_name,
                    'name': name,
                    'brand': brand,
                    'category': category,
                    'price': price,
                    'is_available': is_available,
                    'image_url': image_url,
                    'sku': sku,
                    'platform': platform_name.lower(),
                })

            except Exception as e:
                continue

        log(f"  [{platform_name}] Extracted {len(items)} items", worker_id)
        return items

    except Exception as e:
        log(f"  [{platform_name}] ERROR extracting items: {str(e)[:150]}", worker_id)
        return items


def scroll_table(page, platform_name, worker_id):
    """Scroll through table to load all content"""
    try:
        page.wait_for_timeout(1500)
        table_body = page.locator('.ant-table-body').first

        if table_body.count() == 0:
            return False

        scroll_height = table_body.evaluate("el => el.scrollHeight")
        scroll_width = table_body.evaluate("el => el.scrollWidth")

        scroll_step_y = 300
        scroll_step_x = 400
        current_y = 0

        while current_y < scroll_height:
            current_x = 0
            while current_x < scroll_width:
                table_body.evaluate(f"el => {{ el.scrollTop = {current_y}; el.scrollLeft = {current_x}; }}")
                page.wait_for_timeout(400)
                current_x += scroll_step_x
                if current_x >= scroll_width:
                    break
            current_y += scroll_step_y
            if current_y >= scroll_height:
                break

        # Reset to top-left
        table_body.evaluate("el => { el.scrollTop = 0; el.scrollLeft = 0; }")
        page.wait_for_timeout(300)
        return True

    except Exception as e:
        log(f"  [{platform_name}] ERROR scrolling: {str(e)[:100]}", worker_id)
        return False


def process_all_platforms_for_outlet(page, outlet_name, worker_id):
    """
    Process all 3 platforms for a SINGLE outlet.
    This mirrors the original script's approach but within a parallel worker.
    """
    all_items = []

    platforms = [
        ("Grab", "grab"),
        ("Deliveroo", "deliveroo"),
        ("foodPanda", "foodPanda")
    ]

    for platform_display, platform_key in platforms:
        try:
            # Switch to platform tab
            tab_selector = f'div[data-node-key="{platform_key}"]'
            tab = page.locator(tab_selector).first

            if tab.count() == 0:
                log(f"  [{platform_display}] Tab not found", worker_id)
                continue

            tab.click(timeout=2000)
            page.wait_for_timeout(1000)

            # Set page size to 100
            page_size_selector = page.locator('.ant-select-selection-item').filter(has_text="/ page").first
            if page_size_selector.count() > 0:
                page_size_selector.click(timeout=2000)
                page.wait_for_timeout(800)
                page.locator('div[title="100 / page"]').click(timeout=2000)
                page.wait_for_timeout(1000)

            # Check if bound
            not_bound = page.locator('text=This store has not yet been bound to a third-party platform store').count() > 0
            if not_bound:
                log(f"  [{platform_display}] Not bound - skipping", worker_id)
                continue

            # Scroll and extract
            scroll_table(page, platform_display, worker_id)
            items = extract_items_from_table(page, outlet_name, platform_display, worker_id)
            all_items.extend(items)

        except Exception as e:
            log(f"  [{platform_display}] ERROR: {str(e)[:100]}", worker_id)
            continue

    # Return to Grab tab
    try:
        grab_tab = page.locator('div[data-node-key="grab"]').first
        if grab_tab.count() > 0:
            grab_tab.click(timeout=2000)
            page.wait_for_timeout(800)
    except:
        pass

    return all_items


def worker_process_outlets(worker_id, outlets_to_process):
    """
    Worker function that processes a subset of outlets.
    Each worker has its own browser instance.
    """
    log(f"Starting with {len(outlets_to_process)} outlets to process", worker_id)

    items_collected = []
    outlets_processed = 0

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, slow_mo=800)
        page = browser.new_page()
        page.set_viewport_size({"width": 1920, "height": 1080})

        try:
            # Login
            log(f"Navigating to {BASE_URL}/takeaway-product-mapping", worker_id)
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)

            if "/login" in page.url:
                log("Login page detected, entering credentials...", worker_id)
                page.fill("#username", EMAIL)
                page.click('button[type="submit"]')
                page.wait_for_timeout(2000)
                page.fill("#password", PASSWORD)
                page.click('button[type="submit"]')
                page.wait_for_timeout(5000)
                page.goto(f"{BASE_URL}/takeaway-product-mapping")
                page.wait_for_timeout(5000)

            log("Logged in successfully", worker_id)

            # Navigate to Group view
            log("Navigating to Group view...", worker_id)

            dropdown_selectors = ['div.cursor-pointer.rounded-md', '[class*="cursor-pointer"]']
            for selector in dropdown_selectors:
                try:
                    dropdown = page.locator(selector).first
                    if dropdown.count() > 0:
                        dropdown.click(timeout=3000)
                        page.wait_for_timeout(1500)
                        break
                except:
                    continue

            page.locator('text=Group').first.click(timeout=5000)
            page.wait_for_timeout(2000)
            page.locator('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD').first.click(timeout=5000)
            page.wait_for_timeout(3000)

            sidebar_exists = page.locator('.ant-tree').count() > 0
            if not sidebar_exists:
                log("ERROR: Sidebar not visible", worker_id)
                browser.close()
                return items_collected

            log("Group view ready", worker_id)

            # Close all brands first
            for brand_name, _ in BRANDS:
                try:
                    brand_title = page.locator(f'span[title="{brand_name}"]').first
                    if brand_title.count() > 0:
                        brand_node = brand_title.locator('xpath=ancestor::div[@role="treeitem"]').first
                        switcher = brand_node.locator('.ant-tree-switcher').first
                        switcher_class = switcher.get_attribute('class')
                        if 'ant-tree-switcher_open' in switcher_class:
                            switcher.click(timeout=2000)
                            page.wait_for_timeout(500)
                except:
                    pass

            # Group outlets by brand for this worker
            brand_outlets = {}
            for brand_name, outlet_name in outlets_to_process:
                if brand_name not in brand_outlets:
                    brand_outlets[brand_name] = []
                brand_outlets[brand_name].append(outlet_name)

            # Process each brand
            for brand_name, outlet_names in brand_outlets.items():
                log(f"Processing brand: {brand_name} ({len(outlet_names)} outlets)", worker_id)

                try:
                    # Expand brand
                    brand_title = page.locator(f'span[title="{brand_name}"]').first
                    brand_node = brand_title.locator('xpath=ancestor::div[@role="treeitem"]').first
                    switcher = brand_node.locator('.ant-tree-switcher').first
                    switcher_class = switcher.get_attribute('class')

                    if 'ant-tree-switcher_close' in switcher_class:
                        switcher.click(timeout=3000)
                        page.wait_for_timeout(1500)

                    # Process each outlet
                    for outlet_name in outlet_names:
                        try:
                            log(f"Processing outlet: {outlet_name}", worker_id)

                            # Find and click outlet
                            store_elements = page.locator('.ant-tree-node-content-wrapper').all()

                            for store_elem in store_elements:
                                store_title = store_elem.get_attribute('title')
                                if store_title == outlet_name and store_elem.is_visible():
                                    store_elem.click(timeout=2000)
                                    page.wait_for_timeout(2000)

                                    # Process all 3 platforms for this outlet
                                    items = process_all_platforms_for_outlet(page, outlet_name, worker_id)

                                    if items:
                                        items_collected.extend(items)
                                        # Save immediately
                                        inserted, updated, changes = save_items_with_history(items, worker_id)
                                        log(f"  {outlet_name}: {len(items)} items (new:{inserted}, updated:{updated}, changes:{changes})", worker_id)

                                        # Save shop info
                                        save_shop({
                                            'shop_id': outlet_name,
                                            'shop_name': outlet_name,
                                            'organization_name': 'ACHIEVERS RESOURCE CONSULTANCY PTE LTD',
                                            'has_items': True
                                        }, worker_id)
                                    else:
                                        log(f"  {outlet_name}: No items (not bound to any platform)", worker_id)
                                        save_shop({
                                            'shop_id': outlet_name,
                                            'shop_name': outlet_name,
                                            'organization_name': 'ACHIEVERS RESOURCE CONSULTANCY PTE LTD',
                                            'has_items': False
                                        }, worker_id)

                                    outlets_processed += 1
                                    break

                        except Exception as e:
                            log(f"ERROR on outlet {outlet_name}: {str(e)[:100]}", worker_id)
                            continue

                    # Close brand
                    switcher.click(timeout=2000)
                    page.wait_for_timeout(500)

                except Exception as e:
                    log(f"ERROR on brand {brand_name}: {str(e)[:150]}", worker_id)
                    continue

        except Exception as e:
            log(f"FATAL ERROR: {str(e)}", worker_id)
        finally:
            browser.close()

    log(f"Completed! Processed {outlets_processed} outlets, collected {len(items_collected)} items", worker_id)
    return items_collected


def get_all_outlets():
    """Get list of all outlets by scanning once"""
    outlets = []

    log("Scanning for all outlets...")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, slow_mo=500)
        page = browser.new_page()
        page.set_viewport_size({"width": 1920, "height": 1080})

        try:
            # Login
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)

            if "/login" in page.url:
                page.fill("#username", EMAIL)
                page.click('button[type="submit"]')
                page.wait_for_timeout(2000)
                page.fill("#password", PASSWORD)
                page.click('button[type="submit"]')
                page.wait_for_timeout(5000)
                page.goto(f"{BASE_URL}/takeaway-product-mapping")
                page.wait_for_timeout(5000)

            # Navigate to Group view
            dropdown_selectors = ['div.cursor-pointer.rounded-md', '[class*="cursor-pointer"]']
            for selector in dropdown_selectors:
                try:
                    dropdown = page.locator(selector).first
                    if dropdown.count() > 0:
                        dropdown.click(timeout=3000)
                        page.wait_for_timeout(1500)
                        break
                except:
                    continue

            page.locator('text=Group').first.click(timeout=5000)
            page.wait_for_timeout(2000)
            page.locator('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD').first.click(timeout=5000)
            page.wait_for_timeout(3000)

            # Scan each brand for outlets
            for brand_name, expected_count in BRANDS:
                try:
                    brand_title = page.locator(f'span[title="{brand_name}"]').first
                    brand_node = brand_title.locator('xpath=ancestor::div[@role="treeitem"]').first
                    switcher = brand_node.locator('.ant-tree-switcher').first

                    # Expand
                    switcher_class = switcher.get_attribute('class')
                    if 'ant-tree-switcher_close' in switcher_class:
                        switcher.click(timeout=3000)
                        page.wait_for_timeout(1500)

                    # Find outlets
                    store_elements = page.locator('.ant-tree-node-content-wrapper').all()
                    brand_names_list = [b[0] for b in BRANDS]

                    for store_elem in store_elements:
                        store_title = store_elem.get_attribute('title')
                        if (store_title and
                            store_title not in brand_names_list and
                            store_elem.is_visible()):
                            outlets.append((brand_name, store_title))

                    # Close brand
                    switcher.click(timeout=2000)
                    page.wait_for_timeout(500)

                except Exception as e:
                    log(f"Error scanning brand {brand_name}: {str(e)[:100]}")
                    continue

        except Exception as e:
            log(f"Error during outlet scan: {str(e)}")
        finally:
            browser.close()

    log(f"Found {len(outlets)} outlets across {len(BRANDS)} brands")
    return outlets


def distribute_outlets(outlets, num_workers):
    """Distribute outlets evenly across workers"""
    # Sort outlets by brand to keep brand outlets together
    outlets_by_brand = {}
    for brand, outlet in outlets:
        if brand not in outlets_by_brand:
            outlets_by_brand[brand] = []
        outlets_by_brand[brand].append((brand, outlet))

    # Distribute brands round-robin to workers
    worker_assignments = [[] for _ in range(num_workers)]
    brands_list = list(outlets_by_brand.keys())

    for i, brand in enumerate(brands_list):
        worker_idx = i % num_workers
        worker_assignments[worker_idx].extend(outlets_by_brand[brand])

    return worker_assignments


def main():
    """Main entry point"""
    log("="*70)
    log("SCRAPE ITEMS SYNC V2 - PARALLEL BY OUTLET + TIMESTAMP-BASED")
    log("="*70)

    start_time = datetime.now()

    # Step 1: Get all outlets
    outlets = get_all_outlets()

    if not outlets:
        log("No outlets found! Exiting.")
        return

    # Step 2: Distribute outlets across workers
    worker_assignments = distribute_outlets(outlets, NUM_WORKERS)

    for i, assignment in enumerate(worker_assignments):
        log(f"Worker {i}: {len(assignment)} outlets")

    # Step 3: Run parallel workers
    log("="*70)
    log(f"STARTING {NUM_WORKERS} PARALLEL WORKERS")
    log("="*70)

    results = {}

    with ThreadPoolExecutor(max_workers=NUM_WORKERS) as executor:
        futures = {}

        for worker_id, outlets_subset in enumerate(worker_assignments):
            if outlets_subset:  # Only start worker if it has outlets to process
                future = executor.submit(worker_process_outlets, worker_id, outlets_subset)
                futures[future] = worker_id

        for future in as_completed(futures):
            worker_id = futures[future]
            try:
                items = future.result()
                results[worker_id] = items
                log(f"Worker {worker_id} completed with {len(items)} items")
            except Exception as e:
                log(f"ERROR in Worker {worker_id}: {str(e)}")
                results[worker_id] = []

    # Summary
    total_items = sum(len(items) for items in results.values())

    end_time = datetime.now()
    duration = (end_time - start_time).total_seconds()

    log("="*70)
    log("FINAL SUMMARY")
    log("="*70)
    log(f"Total outlets: {len(outlets)}")
    log(f"Total items collected: {total_items}")
    for worker_id, items in sorted(results.items()):
        log(f"  Worker {worker_id}: {len(items)} items")
    log(f"Total time: {duration:.1f} seconds ({duration/60:.1f} minutes)")
    log("="*70)


if __name__ == "__main__":
    main()
