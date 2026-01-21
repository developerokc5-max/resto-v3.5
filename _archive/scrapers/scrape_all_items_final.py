#!/usr/bin/env python3
"""
FINAL Working Items Scraper
Clicks the correct store selector dropdown (NOT language, NOT account)
Uses XPath/CSS to target the specific dropdown in the Item Mapping section
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
    log("FINAL WORKING ITEMS SCRAPER")
    log("="*70)

    result = {"success": False, "stores": {}, "total_items": 0}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
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

            # Find the CORRECT store selector dropdown
            # It's in the Item Mapping content area, NOT in the header
            # Strategy: look for ant-select that's near "Item Mapping" text
            log("\nStep 3: Finding store selector...")

            # Wait for page to load
            page.wait_for_selector("text=Item Mapping", timeout=10000)
            page.wait_for_timeout(2000)

            # The store selector is typically an ant-select element
            # that's in the main content area (not header)
            # We can find it by looking for ant-select elements and clicking the right one

            # Get all ant-select-selector elements
            selectors = page.query_selector_all(".ant-select-selector")
            log(f"  Found {len(selectors)} dropdowns total")

            # Click the one that's in the content area (usually the 2nd or 3rd one)
            # Skip the language selector (first) and find the store one
            store_selector = None

            for idx, sel in enumerate(selectors):
                # Get bounding box to see position
                try:
                    box = sel.bounding_box()
                    if box:
                        log(f"  Selector {idx}: x={box['x']:.0f}, y={box['y']:.0f}, w={box['width']:.0f}, h={box['height']:.0f}")

                        # The store selector from screenshot is around y=209 based on logs
                        # It's the larger one (width > 250) in the middle-left area
                        # Skip the tiny ones (width < 200) and the far-right ones (x > 1200)
                        if box['y'] < 250 and 200 < box['x'] < 1000 and box['width'] > 250:
                            store_selector = sel
                            log(f"  → This looks like the store selector!")
                            break
                except Exception as e:
                    log(f"  Error checking selector {idx}: {e}")

            if not store_selector:
                log("  Couldn't find store selector by position, trying selector 1...")
                # Fallback: selector at x=256, y=209, w=314 looks promising
                if len(selectors) >= 2:
                    store_selector = selectors[1]
                    log("  Using selector 1 as fallback")

            if store_selector:
                store_selector.click()
                page.wait_for_timeout(2000)
                log("✓ Store dropdown opened")
            else:
                raise Exception("Could not find store selector dropdown")

            # Get all stores from dropdown
            log("\nStep 4: Getting all stores...")

            stores = []
            seen_stores = set()

            # Scroll through dropdown
            log("  Scrolling through dropdown...")
            for scroll_attempt in range(50):
                store_elements = page.query_selector_all(".ant-select-item-option")

                for elem in store_elements:
                    name = elem.inner_text().strip()
                    # Real store names are long and don't contain "testing"
                    if name and len(name) > 10:
                        if "testing" not in name.lower() and "office" not in name.lower():
                            if name not in seen_stores:
                                stores.append(name)
                                seen_stores.add(name)

                # Scroll virtual list
                try:
                    page.evaluate("document.querySelector('.rc-virtual-list-holder').scrollTop += 500")
                    page.wait_for_timeout(200)
                except:
                    pass

            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            log(f"  ✓ Found {len(stores)} stores\n")
            for idx, store in enumerate(stores[:10], 1):  # Show first 10
                log(f"    {idx}. {store}")
            if len(stores) > 10:
                log(f"    ... and {len(stores)-10} more")

            # Scrape each store
            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")
                log("="*70)

                try:
                    # Select store
                    log("  Selecting store...")
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    # Click the store selector again
                    if store_selector:
                        store_selector.click()
                        page.wait_for_timeout(1000)

                    # Type store name
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

                            # Column mapping
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
