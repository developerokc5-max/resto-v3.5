#!/usr/bin/env python3
"""
Debug script to take screenshot and show table structure
"""

import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        # Login
        print("Logging in...")
        page.goto(f"{BASE_URL}/login", wait_until="networkidle")
        page.wait_for_timeout(2000)

        page.wait_for_selector("#username", timeout=30000)
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')

        page.wait_for_selector("#password", timeout=10000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')

        page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
        page.wait_for_timeout(2000)
        print("✓ Login successful\n")

        # Navigate to Takeaway Product Mapping
        print("Navigating to page...")
        page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
        page.wait_for_timeout(3000)

        # Open dropdown and select a store
        selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
        page.click(selector, timeout=10000)
        page.wait_for_timeout(1500)

        # Click Stores tab
        try:
            page.click("text=Stores", timeout=3000)
            page.wait_for_timeout(500)
        except:
            pass

        # Select HUMFULL @ Taman Jurong
        store_name = "HUMFULL @ Taman Jurong"
        print(f"Selecting store: {store_name}")
        page.keyboard.type(store_name, delay=50)
        page.wait_for_timeout(2000)
        page.keyboard.press("Enter")
        page.wait_for_timeout(4000)

        # Click Grab tab
        page.click("text=Grab", timeout=5000)
        page.wait_for_timeout(2000)

        # Wait for table
        page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
        page.wait_for_timeout(1000)

        # Take screenshot
        print("Taking screenshot...")
        page.screenshot(path="debug_table_with_items.png", full_page=True)
        print("✓ Screenshot saved to debug_table_with_items.png")

        # Get first row and print structure
        rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
        print(f"\nFound {len(rows)} rows")

        if len(rows) > 0:
            first_row = rows[0]
            cells = first_row.query_selector_all("td")
            print(f"\nFirst row has {len(cells)} cells:")
            for idx, cell in enumerate(cells):
                text = cell.inner_text().strip()
                html = cell.inner_html()[:100]
                print(f"  Cell {idx}: '{text}' (HTML: {html}...)")

        browser.close()

if __name__ == "__main__":
    sys.exit(main())
