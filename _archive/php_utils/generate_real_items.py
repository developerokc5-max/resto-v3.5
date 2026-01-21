#!/usr/bin/env python3
"""
Generate Real Items - Using WORKING .first approach
- Click .ant-select-selector.first (store dropdown)
- Scroll to get ALL stores
- Scrape each store with page size 100
- Get all columns including toggles
"""

import json
import sys
import time
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("GENERATE REAL ITEMS - Using Working Method")
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

            # Get ALL stores using organization dropdown → Stores tab
            log("\nStep 3: Opening organization dropdown...")
            # Click on organization dropdown (shows ACHIEVERS RESOURCE...)
            page.click('text=/ACHIEVERS RESOURCE/', timeout=5000)
            page.wait_for_timeout(2000)

            log("  Clicking 'Stores' tab...")
            page.click("text=Stores", timeout=5000)
            page.wait_for_timeout(2000)
            log("✓ Stores tab opened")

            # Get ALL store names from the dropdown
            log("\nStep 4: Getting store list from dropdown...")
            page.wait_for_selector(".ant-dropdown", timeout=10000)
            page.wait_for_timeout(1000)

            stores = []
            seen_stores = set()

            # Scroll through dropdown to get all stores
            log("  Scrolling through dropdown to get all stores...")
            for scroll_attempt in range(40):
                store_items = page.query_selector_all(".ant-dropdown .ant-select-item-option")

                for item in store_items:
                    name = item.inner_text().strip()
                    if name and "testing" not in name.lower() and "office" not in name.lower():
                        if name not in seen_stores:
                            stores.append(name)
                            seen_stores.add(name)

                # Scroll virtual list inside dropdown
                try:
                    page.evaluate("""
                        const vlist = document.querySelector('.ant-dropdown .rc-virtual-list-holder');
                        if (vlist) {
                            vlist.scrollTop += 500;
                        }
                    """)
                    page.wait_for_timeout(200)
                except:
                    pass

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            log(f"  ✓ Found {len(stores)} stores\n")
            for idx, store in enumerate(stores, 1):
                log(f"    {idx}. {store}")

            # Scrape each store
            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")
                log("="*70)

                try:
                    # Select store from organization dropdown → Stores tab
                    log("  Selecting store...")
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    # Click organization dropdown
                    page.click('text=/ACHIEVERS RESOURCE/', timeout=5000)
                    page.wait_for_timeout(1000)

                    # Make sure we're on Stores tab
                    try:
                        page.click("text=Stores", timeout=2000)
                        page.wait_for_timeout(1000)
                    except:
                        pass  # Already on Stores tab

                    # Type store name to filter
                    page.keyboard.type(store_name, delay=50)
                    page.wait_for_timeout(1500)

                    # Press Enter or click matching store
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
                        log("  (page size already 100 or not available)")

                    # Scan items
                    log("  Scanning items...")
                    page.wait_for_timeout(2000)

                    # Scroll right to see all columns
                    page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                    page.wait_for_timeout(1000)

                    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                    items = []

                    for row in rows:
                        try:
                            cells = row.query_selector_all("td")
                            if len(cells) < 19:
                                continue

                            # Get data from correct columns
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
