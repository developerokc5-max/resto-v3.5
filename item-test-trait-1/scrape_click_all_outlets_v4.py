#!/usr/bin/env python3
"""
Click all outlets by expanding each brand one by one
Version 4: Always ensure we're at Group view with sidebar visible
"""

import sys
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_click_outlets_v4.log"

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
        # Look for the sidebar with brands
        sidebar_exists = page.locator('.ant-tree').count() > 0

        if sidebar_exists:
            log("✓ Already at Group view - sidebar visible")
            return True
    except:
        pass

    # Not at group view, navigate there
    log("Not at Group view - navigating back to Group tab...")

    try:
        # Click on the organization dropdown (top right)
        log("  Step 1: Click organization dropdown...")
        # Find the element with text containing ACHIEVERS and click it
        page.locator('text=/.*ACHIEVERS.*/').first.click(timeout=5000)
        page.wait_for_timeout(2000)
        log("  ✓ Dropdown opened")

        # Click on Group tab
        log("  Step 2: Click 'Group' tab...")
        page.locator('text=Group').first.click(timeout=3000)
        page.wait_for_timeout(1500)
        log("  ✓ Group tab clicked")

        # Click on the group item in the list (not the header!)
        # Look for a clickable element that contains the org name
        log("  Step 3: Select group from list...")
        # Try to find and click on the group chip/button
        page.locator('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD').nth(1).click(timeout=3000)
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

# List of brands in order
BRANDS = [
    "Le Le Mee Pok",
    "JKT Western",
    "Drinks Stall",
    "HUMFULL",
    "OK Chicken Rice",
    "AH Huat Hokkien Mee"
]

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=800)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("="*70)
    log("STARTING OUTLET CLICKING TEST V4")
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
        page.screenshot(path="item-test-trait-1/failed_group_view.png")
        browser.close()
        sys.exit(1)

    # Take screenshot of starting state
    page.screenshot(path="item-test-trait-1/step1_group_view_v4.png")
    log("Screenshot saved: step1_group_view_v4.png\n")

    total_outlets_clicked = 0

    # Process each brand
    for idx, brand_name in enumerate(BRANDS, 1):
        log("="*70)
        log(f"BRAND {idx}/{len(BRANDS)}: {brand_name}")
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

            # Now find and click all outlets under this brand
            log(f"Finding outlets under {brand_name}...")
            page.wait_for_timeout(1000)

            # Get all child store nodes that are visible
            outlets_clicked_for_brand = 0

            # Find all visible outlet elements (they have title attribute with @)
            all_store_elements = page.locator('.ant-tree-node-content-wrapper').all()

            for store_elem in all_store_elements:
                try:
                    store_title = store_elem.get_attribute('title')
                    if store_title and '@' in store_title and store_title not in BRANDS:
                        # Check if it's visible
                        if store_elem.is_visible():
                            log(f"  Clicking: {store_title}")
                            store_elem.click(timeout=2000)
                            page.wait_for_timeout(1500)
                            log(f"  ✓ Clicked {store_title}")
                            outlets_clicked_for_brand += 1
                            total_outlets_clicked += 1
                except Exception as e:
                    log(f"  ✗ Failed to click outlet: {str(e)[:100]}")

            log(f"✓ Clicked {outlets_clicked_for_brand} outlets under {brand_name}\n")

            # Close this brand before moving to next (except for last one)
            if idx < len(BRANDS) and not is_closed:
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
    log("="*70)

    # Take final screenshot
    screenshot_path = "item-test-trait-1/all_outlets_clicked_v4.png"
    page.screenshot(path=screenshot_path)
    log(f"Screenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")
