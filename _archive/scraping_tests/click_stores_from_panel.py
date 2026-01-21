#!/usr/bin/env python3
"""
Find the stores panel, scroll it, and click each store button
"""

import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=400)
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

    # Find all clickable store elements on the page
    log("Finding all store buttons on the page...")

    # Scroll through the stores panel to load all stores
    log("Scrolling through stores panel...")

    for scroll_step in range(20):
        # Evaluate script to scroll the stores panel
        page.evaluate("""
            // Find and scroll the stores panel
            const panels = document.querySelectorAll('[class*="scroll"], [class*="overflow"], .ant-drawer-body');
            panels.forEach(panel => {
                if (panel.scrollHeight > panel.clientHeight) {
                    panel.scrollTop += 200;
                }
            });
        """)
        page.wait_for_timeout(500)

    # Now collect all visible store buttons
    log("\nCollecting all visible store buttons...")

    # Find all buttons/elements that contain "@" (store names)
    all_buttons = page.query_selector_all("button, [role='button'], .ant-btn")

    store_buttons = []
    for btn in all_buttons:
        try:
            text = btn.inner_text().strip()
            if "@" in text and len(text) > 10:
                store_buttons.append((text, btn))
                log(f"  Found: {text}")
        except:
            pass

    log(f"\n✓ Found {len(store_buttons)} store buttons\n")

    log("="*70)
    log(f"CLICKING ALL {len(store_buttons)} STORES")
    log("="*70)

    success_count = 0

    for idx, (store_name, store_btn) in enumerate(store_buttons, 1):
        log(f"\n[{idx}/{len(store_buttons)}] {store_name}")

        try:
            # Scroll button into view
            store_btn.scroll_into_view_if_needed()
            page.wait_for_timeout(300)

            # Click it
            store_btn.click(timeout=3000)
            page.wait_for_timeout(1500)

            log(f"  ✓ CLICKED!")
            success_count += 1

        except Exception as e:
            log(f"  ✗ Failed: {str(e)[:80]}")

    log("\n" + "="*70)
    log("CLICKING COMPLETE!")
    log("="*70)
    log(f"Successfully clicked: {success_count}/{len(store_buttons)}")
    log("="*70)

    browser.close()
