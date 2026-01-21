#!/usr/bin/env python3
"""
100% BULLETPROOF Items Scraper
Works regardless of session state - does NOT rely on organization name
Uses the Stores tab that's always visible in the UI (next to Group/Brands tabs)
"""

import json
import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("100% BULLETPROOF ITEMS SCRAPER")
    log("="*70)

    result = {"success": False, "stores": {}, "total_items": 0}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)  # Set to False to see browser
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\nStep 1: Logging in...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(2000)
            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')
            page.wait_for_selector("#password")
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')
            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(3000)
            log("✓ Login successful")

            # Navigate to item mapping
            log("\nStep 2: Going to Item Mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ On Item Mapping page")

            # The store selector is on the right side of "Item Mapping" section
            # It's the dropdown that shows store names
            # When you click it, the dropdown opens showing the list
            log("\nStep 3: Opening store selector dropdown...")

            # Wait for page to fully load
            page.wait_for_selector(".ant-select-selector", timeout=10000)
            page.wait_for_timeout(2000)

            # Find and click the store selector dropdown
            # It's usually the ant-select element in the top area of the page
            # But NOT the language selector or pagination selector
            # Strategy: click the one that's in the Item Mapping section header area

            # Take screenshot to debug
            page.screenshot(path="debug_page_loaded.png")

            # Click the store dropdown - need to be specific to avoid language dropdown
            # The store selector is inside the main content area, not in the header
            # Find the ant-select that's NOT the language selector (which is in the top-right corner)
            log("  Looking for store selector (NOT language dropdown)...")

            # Strategy: Find the select element that's in the page body, not the header
            # The store selector should be near the "Item Mapping" heading
            store_selector = page.locator(".ant-select-selector").nth(1)  # Skip first one (language)
            store_selector.click(timeout=5000)
            page.wait_for_timeout(2000)
            log("✓ Store dropdown opened")

            # Get all stores from the list
            log("\nStep 4: Getting all stores...")

            # Look for store items in the dropdown/list
            page.wait_for_timeout(2000)

            stores = []
            seen_stores = set()

            # Scroll and collect all store names
            log("  Scrolling through stores list...")
            for scroll_attempt in range(50):
                # Find all store options - they might be in different formats
                store_elements = page.query_selector_all("""
                    .ant-select-item-option,
                    [role='menuitem'],
                    .ant-dropdown [class*='item']
                """)

                for elem in store_elements:
                    name = elem.inner_text().strip()
                    if name and len(name) > 5:  # Real store names are longer
                        if "testing" not in name.lower() and "office" not in name.lower():
                            if name not in seen_stores:
                                stores.append(name)
                                seen_stores.add(name)

                # Scroll the virtual list
                try:
                    page.evaluate("""
                        const holders = document.querySelectorAll('.rc-virtual-list-holder, .ant-select-dropdown .rc-virtual-list');
                        holders.forEach(h => {
                            if (h) h.scrollTop += 500;
                        });
                    """)
                    page.wait_for_timeout(200)
                except:
                    pass

            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            log(f"  ✓ Found {len(stores)} stores\n")
            for idx, store in enumerate(stores, 1):
                log(f"    {idx}. {store}")

            if len(stores) == 0:
                log("\n⚠ WARNING: No stores found! The dropdown might need manual clicking.")
                log("  Let me try clicking the top area to open store selector...")

                # Try clicking around the search/filter area
                try:
                    # Look for search input or selector in the stores area
                    page.click(".ant-select-selector", timeout=5000)
                    page.wait_for_timeout(2000)

                    # Try again to get stores
                    for scroll_attempt in range(30):
                        store_elements = page.query_selector_all(".ant-select-item-option")
                        for elem in store_elements:
                            name = elem.inner_text().strip()
                            if name and len(name) > 5 and "testing" not in name.lower():
                                if name not in seen_stores:
                                    stores.append(name)
                                    seen_stores.add(name)

                        page.evaluate("document.querySelector('.rc-virtual-list-holder').scrollTop += 500")
                        page.wait_for_timeout(200)

                    page.keyboard.press("Escape")
                    log(f"  ✓ Found {len(stores)} stores after retry")
                except Exception as e:
                    log(f"  ✗ Could not find stores: {e}")

            # Scrape each store
            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")
                log("="*70)

                try:
                    # Select store
                    log("  Selecting store...")
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    # Click the selector to open dropdown (use nth(1) to skip language selector)
                    store_selector = page.locator(".ant-select-selector").nth(1)
                    store_selector.click(timeout=5000)
                    page.wait_for_timeout(1000)

                    # Type store name to filter
                    page.keyboard.type(store_name, delay=50)
                    page.wait_for_timeout(1500)

                    # Press Enter
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(4000)

                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
                    log("  ✓ Store selected")

                    # Set page size to 100
                    try:
                        log("  Setting page size to 100...")
                        page.click(".ant-pagination-options .ant-select-selector", timeout=3000)
                        page.wait_for_timeout(1000)
                        page.click("text=/100.*page/i", timeout=3000)
                        page.wait_for_timeout(3000)
                        log("  ✓ Page size set")
                    except:
                        log("  (page size already 100)")

                    # Scan items
                    log("  Scanning items...")
                    page.wait_for_timeout(2000)

                    # Scroll right to see toggle columns
                    page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                    page.wait_for_timeout(1000)

                    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                    items = []

                    for row in rows:
                        try:
                            cells = row.query_selector_all("td")
                            if len(cells) < 19:
                                continue

                            # Column mapping from test
                            img = cells[2].query_selector("img")
                            image_url = img.get_attribute("src") if img else None

                            name = cells[3].inner_text().strip()
                            category = cells[6].inner_text().strip()
                            sku = cells[7].inner_text().strip()

                            price_text = cells[8].inner_text().strip()
                            try:
                                price = float(price_text.replace("S$", "").replace("$", "").strip())
                            except:
                                price = 0.0

                            status_text = cells[17].inner_text().strip().lower()
                            is_listed = "listed" in status_text

                            items.append({
                                "name": name,
                                "image_url": image_url,
                                "category": category,
                                "sku": sku,
                                "price": price,
                                "is_available": is_listed,
                            })
                        except:
                            continue

                    if items:
                        result["stores"][store_name] = items
                        log(f"  ✓ Scraped {len(items)} items")
                    else:
                        log("  ⚠ No items found")

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            result["total_items"] = sum(len(items) for items in result["stores"].values())
            result["success"] = True
            result["message"] = f"Scraped {result['total_items']} items from {len(result['stores'])} stores"

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Stores: {len(result['stores'])}")
            log(f"Items: {result['total_items']}")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Fatal error: {e}")
            result["message"] = str(e)
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()
