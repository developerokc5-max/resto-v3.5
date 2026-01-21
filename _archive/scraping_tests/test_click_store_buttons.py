#!/usr/bin/env python3
"""
Test clicking the store buttons/chips directly from the Stores panel
"""

import sys
from playwright.sync_api import sync_playwright

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

    # Take screenshot of initial state
    page.screenshot(path="test_initial.png", full_page=True)
    log("Screenshot saved: test_initial.png")

    # Open the store selector
    log("\nOpening store selector...")
    selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
    page.click(selector)
    page.wait_for_timeout(2000)

    # Click on Stores tab
    log("Clicking Stores tab...")
    try:
        page.click("text=Stores")
        page.wait_for_timeout(2000)
    except:
        pass

    # Take screenshot of dropdown/panel
    page.screenshot(path="test_stores_panel.png", full_page=True)
    log("Screenshot saved: test_stores_panel.png")

    # Now look for store buttons - they appear to be in a panel on the right side
    # Let's find all clickable store elements
    log("\nLooking for store buttons...")

    # Try different selectors for the store buttons/chips
    store_buttons = page.query_selector_all(".ant-tag, button, [role='button']")
    log(f"Found {len(store_buttons)} potential clickable elements")

    # Filter to ones that look like store names (contain @)
    store_elements = []
    for btn in store_buttons:
        try:
            text = btn.inner_text().strip()
            if "@" in text and len(text) > 10:
                store_elements.append((text, btn))
                log(f"  Found store button: {text}")
        except:
            pass

    log(f"\nFound {len(store_elements)} store buttons\n")

    # Test clicking the first 10 store buttons
    log("="*70)
    log("TESTING: Clicking first 10 store buttons")
    log("="*70)

    for idx, (store_name, store_btn) in enumerate(store_elements[:10], 1):
        log(f"\n[{idx}/10] Clicking: {store_name}")

        try:
            # Close any open panel first
            try:
                page.keyboard.press("Escape")
                page.wait_for_timeout(500)
            except:
                pass

            # Open store selector again
            page.click(selector)
            page.wait_for_timeout(1500)

            # Click Stores tab
            try:
                page.click("text=Stores")
                page.wait_for_timeout(500)
            except:
                pass

            # Find and click this specific store button again (need to requery after panel reopens)
            store_btn_fresh = page.locator(f"text={store_name}").first
            store_btn_fresh.click(timeout=5000)
            page.wait_for_timeout(2000)

            # Verify selection
            selected_text = page.locator(selector).inner_text(timeout=2000)
            if store_name in selected_text:
                log(f"  ✓ SUCCESS: {store_name} selected!")
            else:
                log(f"  ✗ FAILED: Selected text shows: {selected_text[:60]}")

            # Take screenshot
            page.screenshot(path=f"test_store_{idx}.png")
            log(f"  Screenshot saved: test_store_{idx}.png")

        except Exception as e:
            log(f"  ✗ ERROR: {e}")

    log("\n" + "="*70)
    log("TEST COMPLETE - Check screenshots to verify selections")
    log("="*70)

    browser.close()
