#!/usr/bin/env python3
"""
Click all outlets by expanding each brand one by one
Version 8: Find ALL 46 outlets (2+2+2+18+18+4)
Fix: Include Testing Outlets and all variations
"""

import sys
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_click_outlets_v8.log"

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
    log("STARTING OUTLET CLICKING TEST V8")
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
        page.screenshot(path="item-test-trait-1/failed_group_view_v8.png")
        browser.close()
        sys.exit(1)

    # Take screenshot of starting state
    page.screenshot(path="item-test-trait-1/step1_group_view_v8.png")
    log("Screenshot saved: step1_group_view_v8.png\n")

    total_outlets_clicked = 0
    clicked_outlet_names = []  # Track what we've clicked to avoid duplicates

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

            # Now find and click ONLY outlets under THIS brand
            log(f"Finding outlets under {brand_name}...")
            page.wait_for_timeout(1500)

            # BETTER APPROACH: Find child nodes directly under this brand node
            # Get all tree nodes that are children of this brand
            outlets_clicked_for_brand = 0

            # Find all treenode elements that are children (have higher indent level)
            all_tree_nodes = page.locator('.ant-tree-treenode').all()

            # Get the brand node's indent level
            brand_indent_count = brand_node.locator('.ant-tree-indent').count()

            for tree_node in all_tree_nodes:
                try:
                    # Check if this is a child of the current brand
                    # Children have one more indent than parent
                    node_indent_count = tree_node.locator('.ant-tree-indent').count()

                    if node_indent_count == brand_indent_count + 1:
                        # This is a direct child of the brand
                        # Get the title
                        title_elem = tree_node.locator('.ant-tree-node-content-wrapper').first
                        store_title = title_elem.get_attribute('title')

                        if store_title and store_title not in clicked_outlet_names:
                            # Check if visible
                            if title_elem.is_visible():
                                log(f"  Clicking: {store_title}")
                                title_elem.click(timeout=2000)
                                page.wait_for_timeout(1500)
                                log(f"  ✓ Clicked {store_title}")
                                clicked_outlet_names.append(store_title)
                                outlets_clicked_for_brand += 1
                                total_outlets_clicked += 1

                except Exception as e:
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

    # Take final screenshot
    screenshot_path = "item-test-trait-1/all_outlets_clicked_v8.png"
    page.screenshot(path=screenshot_path)
    log(f"Screenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")
