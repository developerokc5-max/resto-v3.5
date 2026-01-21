#!/usr/bin/env python3
"""
FINAL WORKING SCRAPER - Based on proven bulletproof approach
- Uses the TYPING method (not clicking) which actually works
- Processes ALL stores
- Gets data from ALL 3 platforms (Grab, deliveroo, foodPanda)
- Sets page size to 100
- Scrolls table to get all data
- 100% real-time data
"""

import sys
import json
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

PLATFORMS = ["Grab", "deliveroo", "foodPanda"]

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("FINAL WORKING SCRAPER - All Stores x All Platforms")
    log("="*70)

    result = {
        "stores": [],
        "total_stores": 0,
        "data": {}
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\nStep 1: Logging in...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(3000)

            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')

            page.wait_for_selector("#password", timeout=10000)
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')

            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(3000)
            log("✓ Login successful")

            # Navigate
            log("\nStep 2: Navigating to Item Mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Click the store selector dropdown
            log("\nStep 3: Opening store selector...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)
            log("✓ Dropdown opened")

            # Click Stores tab
            log("\nStep 4: Clicking Stores tab...")
            page.click("text=Stores")
            page.wait_for_timeout(2000)
            log("✓ On Stores tab")

            # Get all stores
            log("\nStep 5: Getting all stores...")
            stores = []
            seen = set()

            for scroll_attempt in range(30):
                all_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span")

                for elem in all_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Real store names contain @ or are long brand names
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"]:
                                # Only single-line store names
                                if "\n" not in text and text not in seen:
                                    stores.append(text)
                                    seen.add(text)
                    except:
                        continue

                # Scroll
                try:
                    page.evaluate("""
                        const dropdowns = document.querySelectorAll('.ant-dropdown, .rc-virtual-list-holder');
                        dropdowns.forEach(d => {
                            if (d.scrollHeight > d.clientHeight) {
                                d.scrollTop += 300;
                            }
                        });
                    """)
                    page.wait_for_timeout(300)
                except:
                    pass

            log(f"✓ Found {len(stores)} stores")

            result["stores"] = stores
            result["total_stores"] = len(stores)

            # Save store list
            with open("final_results.json", "w") as f:
                json.dump(result, f, indent=2)
            log("✓ Store list saved")

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Process each store
            log("\n" + "="*70)
            log(f"Step 6: Processing {len(stores)} stores x 3 platforms...")
            log("="*70)

            for store_idx, store_name in enumerate(stores, 1):
                log(f"\n{'='*70}")
                log(f"[{store_idx}/{len(stores)}] {store_name}")
                log(f"{'='*70}")

                result["data"][store_name] = {}

                try:
                    # IMPORTANT: If we're on first store OR just finished a store,
                    # the dropdown is closed. Need to open it fresh each time.

                    # First, make sure any previous dropdown is closed
                    try:
                        page.keyboard.press("Escape")
                        page.wait_for_timeout(500)
                    except:
                        pass

                    # Open dropdown
                    log("  Opening dropdown...")
                    page.click(selector, timeout=5000)
                    page.wait_for_timeout(1500)

                    # Click Stores tab (only if it's visible/clickable)
                    log("  Clicking Stores tab...")
                    try:
                        page.click("text=Stores", timeout=3000)
                        page.wait_for_timeout(1500)
                    except:
                        # Already on Stores tab, that's fine
                        log("  (already on Stores tab)")
                        page.wait_for_timeout(500)

                    # Type store name to filter
                    log(f"  Typing store name...")
                    page.keyboard.type(store_name, delay=50)
                    page.wait_for_timeout(1500)

                    # Press Enter
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(3000)
                    log(f"  ✓ Store selected")

                    # Check if store is bound
                    page_text = page.content()
                    if "not yet been bound to a third-party platform" in page_text:
                        log(f"  ⚠ Store not bound - skipping all platforms")
                        result["data"][store_name] = "NOT_BOUND"

                        # Save progress
                        with open("final_results.json", "w") as f:
                            json.dump(result, f, indent=2)
                        continue

                    # Process each platform
                    for platform in PLATFORMS:
                        log(f"\n    Platform: {platform}")

                        try:
                            # Click platform tab
                            log(f"      Clicking {platform} tab...")
                            page.click(f"text={platform}")
                            page.wait_for_timeout(2500)

                            # Set page size to 100
                            try:
                                log("      Setting page size to 100...")
                                page.click(".ant-pagination-options .ant-select-selector", timeout=3000)
                                page.wait_for_timeout(1000)
                                page.click("text=/100.*page/i", timeout=3000)
                                page.wait_for_timeout(3000)
                                log("      ✓ Page size set to 100")
                            except:
                                log("      (page size already 100)")

                            # Scroll table left to right to see all columns
                            log("      Scrolling table...")
                            page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                            page.wait_for_timeout(1000)

                            # Get all items from table
                            log("      Scanning items...")
                            rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")

                            items = []
                            for row in rows:
                                try:
                                    cells = row.query_selector_all("td")
                                    if len(cells) < 19:
                                        continue

                                    # Extract item data
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

                                    # Column 17 (0-indexed) contains the availability status
                                    status_text = cells[17].inner_text().strip().lower()
                                    is_available = "listed" in status_text

                                    items.append({
                                        "name": name,
                                        "image_url": image_url,
                                        "category": category,
                                        "sku": sku,
                                        "price": price,
                                        "is_available": is_available,
                                    })
                                except:
                                    continue

                            result["data"][store_name][platform] = items
                            log(f"      ✓ Found {len(items)} items")

                            # Save progress after each platform
                            with open("final_results.json", "w") as f:
                                json.dump(result, f, indent=2)

                        except Exception as e:
                            log(f"      ✗ Error: {e}")
                            result["data"][store_name][platform] = f"ERROR: {str(e)}"

                except Exception as e:
                    log(f"  ✗ Error processing store: {e}")
                    continue

            log("\n" + "="*70)
            log("SCRAPING COMPLETE!")
            log(f"Stores processed: {len(result['data'])}/{result['total_stores']}")

            # Count total items
            total_items = 0
            for store_data in result["data"].values():
                if isinstance(store_data, dict):
                    for platform_data in store_data.values():
                        if isinstance(platform_data, list):
                            total_items += len(platform_data)

            log(f"Total items scraped: {total_items}")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Fatal error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

    log("\n✓ Results saved to final_results.json")

if __name__ == "__main__":
    main()
