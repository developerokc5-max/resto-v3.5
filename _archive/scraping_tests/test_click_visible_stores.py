#!/usr/bin/env python3
"""
Click visible store buttons from the right panel
"""

import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=500)
    context = browser.new_context()
    page = context.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    # Login
    log("Logging in...")
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

    log("✓ Logged in\n")

    # Look for the store buttons that are visible on the page
    # Based on the screenshot, they appear as buttons/chips with store names
    log("Looking for visible store buttons...")

    # List of stores to test (from your list)
    test_stores = [
        "HUMFULL @ Taman Jurong",
        "OK CHICKEN RICE @ Taman Jurong",
        "Le Le Mee Pok Testing Outlet",
        "JKT Western Testing Outlet",
        "Drinks Stall Testing Outlet",
        "AH HUAT HOKKIEN MEE @ TPY",
        "Le Le Mee Pok @ Toa Payoh",
        "JKT Western @ Toa Payoh",
        "51 Toa Payoh Drinks",
        "HUMFULL @ Punggol"
    ]

    selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'

    log("="*70)
    log(f"TESTING: Clicking {len(test_stores)} stores")
    log("="*70)

    for idx, store_name in enumerate(test_stores, 1):
        log(f"\n[{idx}/{len(test_stores)}] Testing: {store_name}")

        try:
            # Find and click the store button
            # Try to click it directly from the visible page
            store_button = page.get_by_text(store_name, exact=True).first
            store_button.click(timeout=5000)
            page.wait_for_timeout(3000)

            # Check what's now selected in the dropdown
            try:
                selected = page.locator(selector).inner_text(timeout=2000)
                if store_name in selected:
                    log(f"  ✓ SUCCESS: {store_name} is now selected!")
                else:
                    log(f"  ⚠ Selected shows: {selected[:60]}")
            except:
                log(f"  ⚠ Could not verify selection")

            # Take a screenshot
            page.screenshot(path=f"clicked_{idx}_{store_name.replace('/', '_').replace('@', 'at')[:30]}.png")
            log(f"  Screenshot saved")

        except Exception as e:
            log(f"  ✗ ERROR: {e}")

        page.wait_for_timeout(1000)

    log("\n" + "="*70)
    log("TEST COMPLETE!")
    log("="*70)

    browser.close()
