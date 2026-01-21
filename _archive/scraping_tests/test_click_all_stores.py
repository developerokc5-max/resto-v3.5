#!/usr/bin/env python3
"""
Test script - Click through each store to verify selection works
"""

import sys
from playwright.sync_api import sync_playwright
import time

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False)
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

    # Get all stores from dropdown
    log("Getting all stores...")
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

    log(f"✓ Found {len(stores)} stores\n")

    # Close dropdown
    page.keyboard.press("Escape")
    page.wait_for_timeout(1000)

    # Test clicking through each store
    log("="*70)
    log("TESTING: Clicking through each store")
    log("="*70)

    for idx, store_name in enumerate(stores, 1):
        log(f"\n[{idx}/{len(stores)}] Testing: {store_name}")

        try:
            # Open dropdown
            page.click(selector, timeout=5000)
            page.wait_for_timeout(1000)

            # Click Stores tab
            try:
                page.click("text=Stores", timeout=3000)
                page.wait_for_timeout(500)
            except:
                pass

            # Type store name
            page.keyboard.type(store_name, delay=30)
            page.wait_for_timeout(1500)

            # Click on the store option
            try:
                # Method 1: Click the text directly
                store_option = page.locator(f"text={store_name}").first
                store_option.click(timeout=3000)
                page.wait_for_timeout(2000)
                log(f"  ✓ Clicked store option")
            except Exception as e:
                log(f"  ⚠ Could not click option: {e}")
                # Fallback to Enter
                page.keyboard.press("Enter")
                page.wait_for_timeout(2000)

            # Verify selection
            try:
                selected_text = page.locator(selector).inner_text(timeout=2000)
                if store_name in selected_text:
                    log(f"  ✓ VERIFIED: Store selected correctly")
                else:
                    log(f"  ✗ FAILED: Store not selected (showing: {selected_text[:50]})")
            except:
                log(f"  ⚠ Could not verify selection")

            # Small delay before next store
            page.wait_for_timeout(500)

        except Exception as e:
            log(f"  ✗ ERROR: {e}")

    log("\n" + "="*70)
    log("TEST COMPLETE!")
    log("="*70)

    browser.close()
