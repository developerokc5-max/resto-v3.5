#!/usr/bin/env python3
"""
Click all outlets by expanding each brand one by one
Version 9:
- Click ALL visible outlets when brand is expanded (with or without @ in name)
- Track clicked outlets to avoid duplicates
- Process all 3 platforms (Grab, Deliveroo, foodPanda) for EVERY outlet
- For each platform: Switch → Set page size to 100 → Check if bound → Scroll (if bound)
- Scroll each bound platform's table (left-to-right, top-to-bottom) to load all content
- Capture data including images and toggle switches
- Return to Grab tab before moving to next outlet
"""

import sys
import os
import sqlite3
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Database path - SQLite (CORRECT PATH FOR v3.5!)
DB_PATH = os.getenv('DB_DATABASE', r'C:\resto-db-v3.5\database\database.sqlite')

# Log file path - use absolute path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(SCRIPT_DIR, "scrape_items_sync.log")

def log(msg):
    """Log to both console and file"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_msg = f"[{timestamp}] {msg}"
    print(log_msg, file=sys.stderr)
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(log_msg + "\n")

def ensure_group_view(page):
    """
    Ensure we're at the Group tab view with sidebar visible
    This is the main hub where all brands are shown
    CRITICAL: Must click the top dropdown to switch views if needed
    """
    log("Checking if we're at Group view...")

    # ALWAYS click the dropdown to ensure we're in the right context
    # This is necessary because previous user might have selected a single outlet
    try:
        log("  Step 1: Click organization dropdown at top (light grey area)...")

        # Try multiple selectors to find the dropdown
        dropdown_selectors = [
            'div.cursor-pointer.rounded-md',  # Original selector
            '[class*="cursor-pointer"]',       # Any element with cursor-pointer
            'div[role="button"]',              # Button role
        ]

        dropdown_clicked = False
        for selector in dropdown_selectors:
            try:
                dropdown = page.locator(selector).first
                if dropdown.count() > 0:
                    dropdown.click(timeout=3000)
                    page.wait_for_timeout(1500)
                    log("  ✓ Dropdown clicked")
                    dropdown_clicked = True
                    break
            except:
                continue

        if not dropdown_clicked:
            log("  ⚠ Could not find dropdown - trying alternative approach")
            # Try clicking near top of page where dropdown should be
            page.mouse.click(200, 50)
            page.wait_for_timeout(1500)

        # Click on Group tab
        log("  Step 2: Click 'Group' tab...")
        page.locator('text=Group').first.click(timeout=5000)
        page.wait_for_timeout(2000)
        log("  ✓ Group tab clicked")

        # Click on the group item in the list
        log("  Step 3: Select group from list...")
        page.locator('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD').first.click(timeout=5000)
        page.wait_for_timeout(3000)
        log("  ✓ Group selected")

        # Verify sidebar now exists with brands
        page.wait_for_timeout(2000)
        sidebar_exists = page.locator('.ant-tree').count() > 0

        if sidebar_exists:
            log("✓ Successfully navigated to Group view - sidebar with brands visible")
            return True
        else:
            log("✗ Sidebar still not visible after navigation")
            return False

    except Exception as e:
        log(f"✗ Error navigating to Group view: {str(e)[:200]}")
        return False

def check_if_outlet_bound(page):
    """
    Check if outlet shows 'not yet been bound' message
    Returns True if bound (OK to proceed), False if not bound (skip)
    """
    try:
        # Check for the "not yet been bound" message
        not_bound_message = page.locator('text=This store has not yet been bound to a third-party platform store').count() > 0

        if not_bound_message:
            log("    ⚠ Outlet not bound to third-party platform - SKIPPING")
            return False

        return True
    except:
        return True  # If error checking, assume it's OK

def set_page_size_to_100(page):
    """
    Set the page size dropdown to 100 / page
    """
    try:
        log("    Setting page size to 100...")

        # Click on the page size dropdown (bottom right corner)
        # Look for the currently selected page size text (e.g., "20 / page")
        page_size_selector = page.locator('.ant-select-selection-item').filter(has_text="/ page").first

        if page_size_selector.count() > 0:
            page_size_selector.click(timeout=2000)
            page.wait_for_timeout(800)

            # Click on "100 / page" option
            page.locator('div[title="100 / page"]').click(timeout=2000)
            page.wait_for_timeout(1000)
            log("    ✓ Page size set to 100")
            return True
        else:
            log("    ⚠ Page size selector not found")
            return False

    except Exception as e:
        log(f"    ⚠ Could not set page size: {str(e)[:100]}")
        return False

def extract_items_from_table(page, shop_name, platform_name):
    """
    Extract all item data from the current platform's table
    Returns list of item dictionaries
    """
    items = []
    try:
        # Wait for table to be ready
        page.wait_for_timeout(1500)

        # Find all rows in the table
        rows = page.locator('.ant-table-tbody tr').all()

        log(f"      [{platform_name}] Extracting {len(rows)} items...")

        # DEBUG: Log the first row's cell contents AND save HTML to understand table structure
        if len(rows) > 0:
            first_row = rows[0]
            first_cells = first_row.locator('td').all()
            log(f"      [{platform_name}] DEBUG - First row has {len(first_cells)} cells:")
            for idx, cell in enumerate(first_cells[:10]):  # Log first 10 cells
                text = cell.text_content().strip()[:80]  # First 80 chars
                log(f"        Cell {idx}: '{text}'")

            # Save first row HTML to file for inspection
            try:
                first_row_html = first_row.evaluate("el => el.outerHTML")
                debug_html_path = os.path.join(SCRIPT_DIR, f"debug_table_row_{platform_name.lower()}.html")
                with open(debug_html_path, "w", encoding="utf-8") as f:
                    f.write(first_row_html)
                log(f"      [{platform_name}] Saved first row HTML to: {debug_html_path}")
            except Exception as e:
                log(f"      [{platform_name}] Could not save HTML: {str(e)[:100]}")

        for row_idx, row in enumerate(rows):
            try:
                # Extract data from each column - LEFT TO RIGHT scanning
                cells = row.locator('td').all()
                if len(cells) < 5:  # Skip if not enough columns
                    continue

                # SCAN ALL COLUMNS LEFT TO RIGHT (RestoSuite table structure):
                # Column 0: Checkbox (selection)
                # Column 1: # (row number)
                # Column 2: Item picture (image)
                # Column 3: Item name ← THE ACTUAL ITEM NAME!
                # Column 4: Size name
                # Column 5: Item type
                # Column 6: Menu group (category)
                # Column 7: SKU ID
                # Column 8: Price
                # Column 9: Item synchronization status
                # Column 10: Pictures of third-party products
                # Column 11: Third-party item name
                # Column 12: Third-party size name
                # Column 13: Third-party item types
                # Column 14: Third-party item category
                # Column 15: Third-Party SKU ID
                # Column 16: Third-party prices
                # Column 17: Listing status ← TOGGLE SWITCH HERE!
                # Column 18: Operation

                # Get image URL from column 2
                img = cells[2].locator('img').first if len(cells) > 2 else None
                image_url = ""
                if img and img.count() > 0:
                    image_url = img.get_attribute('src') or ""

                # Get item name from column 3
                name_cell = cells[3] if len(cells) > 3 else None
                name = name_cell.text_content().strip() if name_cell else "Unknown"

                # Skip header rows (literal text from table headers)
                if name in ['Item name', 'Unknown', ''] or name.startswith('Item name'):
                    continue

                # Get size name from column 4
                brand_cell = cells[4] if len(cells) > 4 else None
                brand = brand_cell.text_content().strip() if brand_cell else ""

                # Get menu group (category) from column 6
                category_cell = cells[6] if len(cells) > 6 else None
                category = category_cell.text_content().strip() if category_cell else ""

                # Get SKU from column 7
                sku_cell = cells[7] if len(cells) > 7 else None
                sku = sku_cell.text_content().strip() if sku_cell else ""

                # Get price from column 8
                price_cell = cells[8] if len(cells) > 8 else None
                price_text = price_cell.text_content().strip() if price_cell else "0"
                price = 0.0
                try:
                    price = float(price_text.replace('S$', '').replace('$', '').replace(',', '').strip())
                except:
                    pass

                # Get toggle switch status from column 17 (Listing status - THIS IS CRITICAL!)
                # The toggle switch shows if this item is ONLINE or OFFLINE for this specific platform
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
                    'row_index': row_idx + 1  # Track row number for debugging
                })

            except Exception as e:
                log(f"      [{platform_name}] ⚠ Error extracting row {row_idx + 1}: {str(e)[:100]}")
                continue

        log(f"      [{platform_name}] ✓ Extracted {len(items)} items")
        return items

    except Exception as e:
        log(f"      [{platform_name}] ⚠ Error extracting items: {str(e)[:150]}")
        return items


def scroll_table(page, platform_name):
    """
    Scroll through one platform's table (left-to-right, top-to-bottom)
    """
    try:
        log(f"      [{platform_name}] Scrolling table...")

        # Wait for table to load
        page.wait_for_timeout(1500)

        # Find the table body container (the scrollable div)
        table_body = page.locator('.ant-table-body').first

        if table_body.count() == 0:
            log(f"      [{platform_name}] ⚠ Table body not found")
            return []

        # Get scroll dimensions
        scroll_height = table_body.evaluate("el => el.scrollHeight")
        scroll_width = table_body.evaluate("el => el.scrollWidth")
        client_height = table_body.evaluate("el => el.clientHeight")
        client_width = table_body.evaluate("el => el.clientWidth")

        log(f"      [{platform_name}] Table: {scroll_width}x{scroll_height}px (viewport: {client_width}x{client_height}px)")

        # Scroll vertically first (top to bottom)
        scroll_step_y = 300  # Scroll 300px at a time vertically
        current_y = 0

        while current_y < scroll_height:
            # Scroll horizontally at each vertical position (left to right)
            scroll_step_x = 400  # Scroll 400px at a time horizontally
            current_x = 0

            while current_x < scroll_width:
                # Scroll to position
                table_body.evaluate(f"el => {{ el.scrollTop = {current_y}; el.scrollLeft = {current_x}; }}")
                page.wait_for_timeout(400)  # Wait for content to load

                current_x += scroll_step_x

                # If we've reached the end horizontally, break
                if current_x >= scroll_width:
                    break

            current_y += scroll_step_y

            # If we've reached the end vertically, break
            if current_y >= scroll_height:
                break

        # Scroll back to top-left
        table_body.evaluate("el => { el.scrollTop = 0; el.scrollLeft = 0; }")
        page.wait_for_timeout(300)

        # Capture data counts
        try:
            toggle_count = page.locator('.ant-switch').count()
            image_count = page.locator('.ant-table-body img').count()
            row_count = page.locator('.ant-table-tbody tr').count()
            log(f"      [{platform_name}] ✓ Complete - {row_count} rows, {image_count} images, {toggle_count} toggles")
        except Exception as e:
            log(f"      [{platform_name}] ⚠ Error counting elements: {str(e)[:100]}")

        return True

    except Exception as e:
        log(f"      [{platform_name}] ⚠ Error scrolling: {str(e)[:150]}")
        return False

def scroll_and_capture_data(page, outlet_name):
    """
    Process all 3 platform tabs (Grab, Deliveroo, foodPanda)
    For each platform:
    1. Switch to platform tab
    2. Set page size to 100
    3. Check if bound (even unbound platforms need size set and checked)
    4. Scroll if bound, extract items, skip if not bound
    Returns: list of all items from all platforms
    """
    all_items = []

    try:
        log("    Processing all platforms...")

        # Platform tabs to process in order
        platforms = [
            ("Grab", "grab"),
            ("Deliveroo", "deliveroo"),
            ("foodPanda", "foodPanda")
        ]

        for platform_display, platform_key in platforms:
            log(f"    [{platform_display}] Switching...")

            try:
                # Click on the platform tab
                # The tabs have data-node-key attribute (e.g., data-node-key="grab")
                tab_selector = f'div[data-node-key="{platform_key}"]'
                tab = page.locator(tab_selector).first

                if tab.count() == 0:
                    log(f"    [{platform_display}] ⚠ Tab not found")
                    continue

                tab.click(timeout=2000)
                page.wait_for_timeout(1000)
                log(f"    [{platform_display}] ✓ Switched")

                # Set page size to 100 for this platform
                log(f"    [{platform_display}] Setting page size to 100...")
                page_size_selector = page.locator('.ant-select-selection-item').filter(has_text="/ page").first

                if page_size_selector.count() > 0:
                    page_size_selector.click(timeout=2000)
                    page.wait_for_timeout(800)
                    page.locator('div[title="100 / page"]').click(timeout=2000)
                    page.wait_for_timeout(1000)
                    log(f"    [{platform_display}] ✓ Page size set to 100")
                else:
                    log(f"    [{platform_display}] ⚠ Page size selector not found")

                # Check if bound to this platform
                not_bound_message = page.locator('text=This store has not yet been bound to a third-party platform store').count() > 0

                if not_bound_message:
                    log(f"    [{platform_display}] ⚠ Not bound - skipping")
                    continue

                # Platform is bound - scroll through table and extract items
                log(f"    [{platform_display}] Bound - scrolling and extracting...")
                scroll_table(page, platform_display)

                # Extract items from this platform
                items = extract_items_from_table(page, outlet_name, platform_display)
                all_items.extend(items)

            except Exception as e:
                log(f"    [{platform_display}] ⚠ Error: {str(e)[:150]}")
                continue

        # Return to Grab tab for next outlet
        log("    Returning to Grab tab...")
        try:
            grab_tab = page.locator('div[data-node-key="grab"]').first
            if grab_tab.count() > 0:
                grab_tab.click(timeout=2000)
                page.wait_for_timeout(800)
                log("    ✓ Back to Grab tab")
        except Exception as e:
            log(f"    ⚠ Error returning to Grab: {str(e)[:100]}")

        return all_items

    except Exception as e:
        log(f"    ⚠ Error during platform processing: {str(e)[:150]}")
        return all_items

# List of brands in order with expected counts
BRANDS = [
    ("Le Le Mee Pok", 2),
    ("JKT Western", 2),
    ("Drinks Stall", 2),
    ("HUMFULL", 18),
    ("OK Chicken Rice", 18),
    ("AH Huat Hokkien Mee", 4)
]

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, slow_mo=800)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("="*70)
    log("STARTING ITEMS SYNC")
    log("="*70)

    # Connect to database
    log("Connecting to database...")
    log(f"Database path: {DB_PATH}")
    try:
        db = sqlite3.connect(DB_PATH)
        cursor = db.cursor()
        log("✓ Database connected")
    except Exception as e:
        log(f"✗ Database connection failed: {str(e)}")
        sys.exit(1)

    # Clear existing items and shops tables
    log("Clearing old data from database...")
    try:
        cursor.execute("DELETE FROM items")
        cursor.execute("DELETE FROM shops")
        db.commit()
        log("✓ Old data cleared")
    except Exception as e:
        log(f"⚠ Error clearing data: {str(e)}")

    # Login
    log(f"Navigating to {BASE_URL}/takeaway-product-mapping")
    page.goto(f"{BASE_URL}/takeaway-product-mapping")
    page.wait_for_timeout(5000)

    if "/login" in page.url:
        log("Login page detected, entering credentials...")
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_timeout(2000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_timeout(5000)
        page.goto(f"{BASE_URL}/takeaway-product-mapping")
        page.wait_for_timeout(5000)

    log("✓ Logged in successfully\n")

    # CRITICAL: Ensure we're at Group view FIRST
    log("="*70)
    log("ENSURING WE'RE AT GROUP VIEW (MAIN HUB)")
    log("="*70)

    if not ensure_group_view(page):
        log("FAILED to get to Group view - STOPPING")
        page.screenshot(path=os.path.join(SCRIPT_DIR, "failed_group_view.png"))
        browser.close()
        sys.exit(1)

    # Take screenshot of starting state
    page.screenshot(path=os.path.join(SCRIPT_DIR, "step1_group_view.png"))
    log("Screenshot saved: step1_group_view.png\n")

    # STEP 0: Close all expanded brands first to start clean
    log("="*70)
    log("CLOSING ALL EXPANDED BRANDS (START CLEAN)")
    log("="*70)

    for brand_name, _ in BRANDS:
        try:
            # Find the brand's tree node
            brand_title = page.locator(f'span[title="{brand_name}"]').first
            if brand_title.count() > 0:
                brand_node = brand_title.locator('xpath=ancestor::div[@role="treeitem"]').first
                switcher = brand_node.locator('.ant-tree-switcher').first

                # Check if it's expanded (open)
                switcher_class = switcher.get_attribute('class')
                is_open = 'ant-tree-switcher_open' in switcher_class

                if is_open:
                    log(f"  Closing {brand_name}...")
                    switcher.click(timeout=2000)
                    page.wait_for_timeout(800)
                    log(f"  ✓ {brand_name} closed")
        except:
            pass

    log("✓ All brands closed - starting fresh\n")

    total_outlets_clicked = 0
    clicked_outlet_names = set()  # Track what we've clicked to avoid duplicates
    all_collected_items = []  # Store all items from all outlets
    all_outlets_info = []  # Store info about ALL outlets (with or without items)

    # Process each brand
    for idx, (brand_name, expected_count) in enumerate(BRANDS, 1):
        log("="*70)
        log(f"BRAND {idx}/{len(BRANDS)}: {brand_name} (Expected: {expected_count} outlets)")
        log("="*70)

        try:
            # Find the brand's tree node row
            brand_title_element = page.locator(f'span[title="{brand_name}"]').first

            # Get the parent treenode div
            brand_node = brand_title_element.locator('xpath=ancestor::div[@role="treeitem"]').first

            # Get the switcher (arrow) within this node
            switcher = brand_node.locator('.ant-tree-switcher').first

            # Check if it's closed
            switcher_class = switcher.get_attribute('class')
            is_closed = 'ant-tree-switcher_close' in switcher_class

            # If closed, expand it by clicking arrow
            if is_closed:
                log(f"Expanding {brand_name}... (clicking arrow)")
                switcher.click(timeout=3000)
                page.wait_for_timeout(1500)
                log(f"✓ {brand_name} expanded")
            else:
                log(f"{brand_name} already expanded")

            # Now click ALL visible outlets (child nodes under this brand) that we haven't clicked yet
            log(f"Finding outlets under {brand_name}...")
            page.wait_for_timeout(1000)

            outlets_clicked_for_brand = 0

            # Find ALL visible store elements (child nodes)
            all_store_elements = page.locator('.ant-tree-node-content-wrapper').all()

            for store_elem in all_store_elements:
                try:
                    store_title = store_elem.get_attribute('title')

                    # Only click if:
                    # 1. Has a title
                    # 2. NOT a brand name (not in our BRANDS list)
                    # 3. NOT already clicked
                    # 4. Is visible
                    # NOTE: Removed @ requirement - some outlets don't have @ in name (Testing Outlets)
                    if (store_title and
                        store_title not in [b[0] for b in BRANDS] and
                        store_title not in clicked_outlet_names and
                        store_elem.is_visible()):

                        log(f"  Clicking: {store_title}")
                        store_elem.click(timeout=2000)
                        page.wait_for_timeout(2000)
                        log(f"  ✓ Clicked {store_title}")

                        # Process all platforms (Grab, Deliveroo, foodPanda)
                        # For each platform: set size to 100, check if bound, scroll if bound
                        items_from_outlet = scroll_and_capture_data(page, store_title)

                        # Collect items
                        has_items = len(items_from_outlet) > 0
                        if items_from_outlet:
                            all_collected_items.extend(items_from_outlet)
                            log(f"  ✓ Collected {len(items_from_outlet)} items from {store_title}")
                        else:
                            log(f"  ⚠ No items collected from {store_title}")

                        # Track ALL outlets (with or without items)
                        all_outlets_info.append({
                            'shop_id': store_title,
                            'shop_name': store_title,
                            'organization_name': 'ACHIEVERS RESOURCE CONSULTANCY PTE LTD',
                            'has_items': has_items
                        })

                        # Mark as clicked
                        clicked_outlet_names.add(store_title)
                        outlets_clicked_for_brand += 1
                        total_outlets_clicked += 1

                        # NOTE: Sidebar stays visible after clicking outlet, no need to go back!

                except Exception as e:
                    log(f"    Error processing outlet: {str(e)[:150]}")
                    continue

            log(f"✓ Clicked {outlets_clicked_for_brand}/{expected_count} outlets under {brand_name}")

            if outlets_clicked_for_brand != expected_count:
                log(f"⚠ WARNING: Expected {expected_count} but got {outlets_clicked_for_brand}!")

            log("")

            # ALWAYS close this brand before moving to next
            log(f"Closing {brand_name}... (clicking arrow)")
            switcher.click(timeout=3000)
            page.wait_for_timeout(1000)
            log(f"✓ {brand_name} closed\n")

        except Exception as e:
            log(f"✗ Error processing {brand_name}: {str(e)[:200]}")
            log("STOPPING - Please check the error\n")
            break

    log("="*70)
    log("TEST COMPLETE")
    log(f"Total outlets clicked: {total_outlets_clicked}")
    log(f"Expected: 46 outlets")
    if total_outlets_clicked == 46:
        log("✓✓✓ SUCCESS! All 46 outlets clicked!")
    else:
        log(f"✗ Missing {46 - total_outlets_clicked} outlets")
    log("="*70)

    # List all clicked outlets
    log("\nAll clicked outlets:")
    for name in sorted(clicked_outlet_names):
        log(f"  - {name}")

    # Take final screenshot
    screenshot_path = os.path.join(SCRIPT_DIR, "all_outlets_clicked.png")
    page.screenshot(path=screenshot_path)
    log(f"\nScreenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")

    # Save all outlets to shops table first
    log("="*70)
    log("SAVING OUTLETS TO SHOPS TABLE")
    log(f"Total outlets found: {len(all_outlets_info)}")
    log("="*70)

    if all_outlets_info:
        try:
            insert_shop_query = """
                INSERT INTO shops (shop_id, shop_name, organization_name, has_items, last_synced_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, datetime('now'), datetime('now'), datetime('now'))
            """

            shops_data = [
                (
                    outlet['shop_id'],
                    outlet['shop_name'],
                    outlet['organization_name'],
                    1 if outlet['has_items'] else 0
                )
                for outlet in all_outlets_info
            ]

            cursor.executemany(insert_shop_query, shops_data)
            db.commit()
            log(f"✓ Saved {len(all_outlets_info)} outlets to shops table")

            # Show breakdown
            outlets_with_items = sum(1 for o in all_outlets_info if o['has_items'])
            outlets_without_items = len(all_outlets_info) - outlets_with_items
            log(f"  - {outlets_with_items} outlets with items")
            log(f"  - {outlets_without_items} outlets without items")

        except Exception as e:
            log(f"✗ Error saving outlets: {str(e)}")
            db.rollback()
    else:
        log("⚠ No outlets found to save")

    # Save all items to database
    log("="*70)
    log("SAVING ITEMS TO DATABASE")
    log(f"Total items collected: {len(all_collected_items)}")
    log("="*70)

    if all_collected_items:
        try:
            # Prepare batch insert (SQLite uses ? placeholders instead of %s)
            insert_query = """
                INSERT INTO items (shop_id, shop_name, name, category, price, is_available, image_url, sku, platform, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            """

            # Insert items in batches of 100
            batch_size = 100
            inserted_count = 0

            for i in range(0, len(all_collected_items), batch_size):
                batch = all_collected_items[i:i+batch_size]
                batch_data = [
                    (
                        item['shop_id'],
                        item['shop_name'],
                        item['name'],
                        item['category'],
                        item['price'],
                        1 if item['is_available'] else 0,
                        item['image_url'],
                        item['sku'],
                        item['platform']
                    )
                    for item in batch
                ]

                cursor.executemany(insert_query, batch_data)
                db.commit()
                inserted_count += len(batch)
                log(f"  ✓ Inserted batch {i//batch_size + 1} ({inserted_count}/{len(all_collected_items)} items)")

            log(f"✓✓✓ Successfully saved {inserted_count} items to database!")

        except Exception as e:
            log(f"✗ Error saving items to database: {str(e)}")
            db.rollback()
    else:
        log("⚠ No items collected to save")

    # Close database connection
    try:
        cursor.close()
        db.close()
        log("✓ Database connection closed")
    except Exception as e:
        log(f"⚠ Error closing database: {str(e)}")

    log("="*70)
    log("ITEMS SYNC COMPLETE")
    log("="*70)
