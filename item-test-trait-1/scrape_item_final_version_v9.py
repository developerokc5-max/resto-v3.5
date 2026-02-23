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
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_click_outlets_v9.log"

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
    """
    log("Checking if we're at Group view...")

    # Check if sidebar with brands exists
    try:
        sidebar_exists = page.locator('.ant-tree').count() > 0

        if sidebar_exists:
            log("✓ Already at Group view - sidebar visible")
            return True
    except:
        pass

    # Not at group view, navigate there
    log("Not at Group view - navigating back to Group tab...")

    try:
        # Click on the dropdown element (the div with cursor-pointer class)
        log("  Step 1: Click organization dropdown...")
        page.locator('div.cursor-pointer.rounded-md').first.click(timeout=5000)
        page.wait_for_timeout(2000)
        log("  ✓ Dropdown opened")

        # Click on Group tab
        log("  Step 2: Click 'Group' tab...")
        page.locator('text=Group').first.click(timeout=3000)
        page.wait_for_timeout(1500)
        log("  ✓ Group tab clicked")

        # Click on the group item in the list
        log("  Step 3: Select group from list...")
        page.locator('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD').first.click(timeout=3000)
        page.wait_for_timeout(3000)
        log("  ✓ Group selected")

        # Verify sidebar now exists
        sidebar_exists = page.locator('.ant-tree').count() > 0
        if sidebar_exists:
            log("✓ Successfully navigated to Group view - sidebar now visible")
            return True
        else:
            log("✗ Sidebar still not visible after navigation")
            return False

    except Exception as e:
        log(f"✗ Error navigating to Group view: {str(e)}")
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
            return False

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
    4. Scroll if bound, skip scrolling if not bound
    """
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
                    log(f"    [{platform_display}] ⚠ Not bound - skipping scroll")
                    continue

                # Platform is bound - scroll through table
                log(f"    [{platform_display}] Bound - scrolling...")
                scroll_table(page, platform_display)

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

        return True

    except Exception as e:
        log(f"    ⚠ Error during platform processing: {str(e)[:150]}")
        return False

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
    browser = p.chromium.launch(headless=False, slow_mo=800)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("="*70)
    log("STARTING OUTLET CLICKING TEST V9")
    log("="*70)

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
        page.screenshot(path="item-test-trait-1/failed_group_view_v9.png")
        browser.close()
        sys.exit(1)

    # Take screenshot of starting state
    page.screenshot(path="item-test-trait-1/step1_group_view_v9.png")
    log("Screenshot saved: step1_group_view_v9.png\n")

    total_outlets_clicked = 0
    clicked_outlet_names = set()  # Track what we've clicked to avoid duplicates

    # Process each brand
    for idx, (brand_name, expected_count) in enumerate(BRANDS, 1):
        log("="*70)
        log(f"BRAND {idx}/{len(BRANDS)}: {brand_name} (Expected: {expected_count} outlets)")
        log("="*70)

        # Ensure we're still at group view before processing this brand
        if not ensure_group_view(page):
            log(f"Lost Group view - STOPPING at {brand_name}")
            break

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
                        scroll_and_capture_data(page, store_title)

                        # Mark as clicked
                        clicked_outlet_names.add(store_title)
                        outlets_clicked_for_brand += 1
                        total_outlets_clicked += 1

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
    screenshot_path = "item-test-trait-1/all_outlets_clicked_v9.png"
    page.screenshot(path=screenshot_path)
    log(f"\nScreenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")
