#!/usr/bin/env python3
"""
Click all outlets by expanding each brand one by one
Version 3: Click on arrow icons (.ant-tree-switcher) properly
"""

import sys
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_click_outlets_v3.log"

def log(msg):
    """Log to both console and file"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_msg = f"[{timestamp}] {msg}"
    print(log_msg, file=sys.stderr)
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(log_msg + "\n")

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
    log("STARTING OUTLET CLICKING TEST V3")
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

    # CRITICAL STEP: Ensure we're on the Group tab view
    log("="*70)
    log("ENSURING WE'RE ON GROUP TAB VIEW")
    log("="*70)

    try:
        # Click on the top right dropdown (organization selector)
        log("Clicking on organization dropdown...")
        page.click('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD')
        page.wait_for_timeout(2000)
        log("✓ Dropdown opened")

        # Click on Group tab
        log("Clicking on 'Group' tab...")
        page.click('text=Group')
        page.wait_for_timeout(1500)
        log("✓ Group tab selected")

        # Click on ACHIEVERS RESOURCE CONSULTANCY PTE LTD to go to main group view
        log("Clicking on ACHIEVERS RESOURCE CONSULTANCY PTE LTD...")
        page.click('text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD')
        page.wait_for_timeout(3000)
        log("✓ Main group view loaded\n")

    except Exception as e:
        log(f"✗ Error navigating to Group view: {str(e)}")
        log("Attempting to continue anyway...\n")

    # Take screenshot of starting state
    page.screenshot(path="item-test-trait-1/step1_group_view_v3.png")
    log("Screenshot saved: step1_group_view_v3.png\n")

    total_outlets_clicked = 0

    # Process each brand
    for idx, brand_name in enumerate(BRANDS, 1):
        log("="*70)
        log(f"BRAND {idx}/{len(BRANDS)}: {brand_name}")
        log("="*70)

        try:
            # Find the brand's tree node row
            brand_title_element = page.locator(f'[title="{brand_name}"]').first

            # Get the parent treenode div
            brand_node = brand_title_element.locator('xpath=ancestor::div[@role="treeitem"]').first

            # Get the switcher (arrow) within this node
            switcher = brand_node.locator('.ant-tree-switcher').first

            # Check if it's closed
            switcher_class = switcher.get_attribute('class')
            is_closed = 'ant-tree-switcher_close' in switcher_class

            # If not the first brand (or if closed), expand it
            if idx > 1 or is_closed:
                log(f"Expanding {brand_name}... (clicking arrow)")
                switcher.click(timeout=3000)
                page.wait_for_timeout(1500)
                log(f"✓ {brand_name} expanded")

            # Now find and click all outlets under this brand
            log(f"Finding outlets under {brand_name}...")
            page.wait_for_timeout(1000)

            # Get all child nodes (outlets) - they don't have switcher_open/close, they're leaf nodes
            # Look for text elements with @ symbol that are visible
            outlets_clicked_for_brand = 0

            # Get all visible store names with @ in them
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
                    log(f"  ✗ Failed to click outlet: {str(e)}")

            log(f"✓ Clicked {outlets_clicked_for_brand} outlets under {brand_name}\n")

            # Close this brand before moving to next (except for last one)
            if idx < len(BRANDS):
                log(f"Closing {brand_name}... (clicking arrow)")
                switcher.click(timeout=3000)
                page.wait_for_timeout(1000)
                log(f"✓ {brand_name} closed\n")

        except Exception as e:
            log(f"✗ Error processing {brand_name}: {str(e)}")
            log("STOPPING - Please check the error\n")
            break

    log("="*70)
    log("TEST COMPLETE")
    log(f"Total outlets clicked: {total_outlets_clicked}")
    log("="*70)

    # Take final screenshot
    screenshot_path = "item-test-trait-1/all_outlets_clicked_v3.png"
    page.screenshot(path=screenshot_path)
    log(f"Screenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")
