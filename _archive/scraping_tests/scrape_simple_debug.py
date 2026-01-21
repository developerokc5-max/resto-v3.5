#!/usr/bin/env python3
"""Simple debug - just print what we see in the table"""

import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False)
    context = browser.new_context()
    page = context.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    # Login
    print("Logging in...")
    page.goto(f"{BASE_URL}/takeaway-product-mapping")
    page.wait_for_timeout(5000)

    # Check if we need to login
    if "/login" in page.url:
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_timeout(2000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_timeout(5000)
        page.goto(f"{BASE_URL}/takeaway-product-mapping")
        page.wait_for_timeout(5000)

    # Open dropdown and select a store
    print("Selecting store...")
    selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
    page.click(selector)
    page.wait_for_timeout(2000)

    page.keyboard.type("HUMFULL @ Taman Jurong", delay=50)
    page.wait_for_timeout(2000)
    page.keyboard.press("Enter")
    page.wait_for_timeout(4000)

    # Click Grab tab
    page.click("text=Grab")
    page.wait_for_timeout(3000)

    # Try scrolling in case of virtual scrolling
    print("Scrolling table...")
    try:
        page.evaluate("""
            const scrollContainer = document.querySelector('.ant-table-body, [class*="scroll"], [class*="virtual"]');
            if (scrollContainer) {
                scrollContainer.scrollTop = 0;
                console.log('Scrolled to top');
            }
        """)
        page.wait_for_timeout(2000)
    except:
        pass

    # Take screenshot to see what's on screen
    page.screenshot(path="debug_grab_tab.png", full_page=True)
    print("Screenshot saved to debug_grab_tab.png")

    # Get table rows
    rows = page.query_selector_all("table tbody tr")
    print(f"\nFound {len(rows)} total rows\n")

    # Look at first few actual data rows (skip measure row)
    data_rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row)")
    print(f"Data rows (excluding measure): {len(data_rows)}\n")

    for idx, row in enumerate(data_rows[:3]):
        print(f"=== DATA ROW {idx} ===")
        full_text = row.inner_text()
        print(f"Full text: {full_text}")
        print(f"Full HTML:\n{row.inner_html()}\n")

        # Look for all divs and spans
        divs = row.query_selector_all("div")
        spans = row.query_selector_all("span")
        print(f"  Has {len(divs)} divs, {len(spans)} spans")

        # Try to find item name in various ways
        item_elem = row.query_selector('[class*="productName"], [class*="item-name"], .product-name')
        if item_elem:
            print(f"  Found item element: {item_elem.inner_text()}")

        print("\n" + "="*70 + "\n")

    browser.close()
