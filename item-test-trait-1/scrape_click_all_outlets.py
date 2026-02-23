#!/usr/bin/env python3
"""
Click all outlets by expanding each brand one by one
"""

import sys
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_click_outlets.log"

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
    log("STARTING OUTLET CLICKING TEST")
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

    total_outlets_clicked = 0

    # Process each brand
    for idx, brand_name in enumerate(BRANDS, 1):
        log("="*70)
        log(f"BRAND {idx}/{len(BRANDS)}: {brand_name}")
        log("="*70)

        # If not the first brand, need to expand it
        if idx > 1:
            log(f"Expanding {brand_name}...")
            try:
                # Find the brand's switcher arrow and click it
                brand_row = page.locator(f'[title="{brand_name}"]').locator('..').locator('..')
                switcher = brand_row.locator('.ant-tree-switcher').first
                switcher.click(timeout=3000)
                page.wait_for_timeout(1000)
                log(f"✓ {brand_name} expanded")
            except Exception as e:
                log(f"✗ Failed to expand {brand_name}: {str(e)}")
                continue

        # Find all outlets under this brand
        log(f"Finding outlets under {brand_name}...")
        page.wait_for_timeout(1000)

        # Get all store links that are currently visible
        try:
            # Find all treenode items that are children (outlets)
            outlets = page.locator('.ant-tree-treenode-switcher-close .ant-tree-node-content-wrapper, .ant-tree-treenode-leaf-last .ant-tree-node-content-wrapper').all()

            outlet_count = 0
            for outlet in outlets:
                try:
                    outlet_title = outlet.get_attribute('title')
                    if outlet_title:
                        log(f"  Clicking: {outlet_title}")
                        outlet.click(timeout=2000)
                        page.wait_for_timeout(1500)
                        log(f"  ✓ Clicked {outlet_title}")
                        outlet_count += 1
                        total_outlets_clicked += 1
                except Exception as e:
                    log(f"  ✗ Failed to click outlet: {str(e)}")

            log(f"✓ Clicked {outlet_count} outlets under {brand_name}\n")

        except Exception as e:
            log(f"✗ Error finding outlets: {str(e)}\n")

        # Close this brand before moving to next (except for last one)
        if idx < len(BRANDS):
            log(f"Closing {brand_name}...")
            try:
                brand_row = page.locator(f'[title="{brand_name}"]').locator('..').locator('..')
                switcher = brand_row.locator('.ant-tree-switcher').first
                switcher.click(timeout=3000)
                page.wait_for_timeout(1000)
                log(f"✓ {brand_name} closed\n")
            except Exception as e:
                log(f"✗ Failed to close {brand_name}: {str(e)}\n")

    log("="*70)
    log("TEST COMPLETE")
    log(f"Total outlets clicked: {total_outlets_clicked}")
    log("="*70)

    # Take final screenshot
    screenshot_path = "item-test-trait-1/all_outlets_clicked.png"
    page.screenshot(path=screenshot_path)
    log(f"Screenshot saved: {screenshot_path}")

    page.wait_for_timeout(5000)
    browser.close()
    log("Browser closed")
