#!/usr/bin/env python3
"""
REAL-TIME scraper that:
1. Opens Stores tab
2. Clicks FIRST store button, processes it (all 3 platforms)
3. Clicks SECOND store button, processes it
4. Continues until LAST store
5. 100% real-time data, no typing/filtering
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
    log("REAL-TIME CLICK-BASED SCRAPER")
    log("="*70)

    result = {
        "stores_processed": 0,
        "data": {}  # store_name -> platform -> items[]
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

            # Get all store buttons in the dropdown
            log("\nStep 5: Getting store buttons...")

            # Scroll through and collect all unique store names first
            store_names = []
            seen = set()

            for scroll_attempt in range(30):
                # Find all text elements in the dropdown
                all_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span, .ant-dropdown button")

                for elem in all_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Real store names contain @ or are long brand names
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"]:
                                if text not in seen:
                                    store_names.append(text)
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

            log(f"✓ Found {len(store_names)} stores to process")
            for idx, name in enumerate(store_names, 1):
                log(f"  {idx}. {name}")

            # Save store list
            result["total_stores"] = len(store_names)
            result["store_list"] = store_names
            with open("realtime_results.json", "w") as f:
                json.dump(result, f, indent=2)

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Now process each store by clicking them in order
            log("\n" + "="*70)
            log(f"Step 6: Processing {len(store_names)} stores x 3 platforms...")
            log("="*70)

            for store_idx, store_name in enumerate(store_names, 1):
                log(f"\n{'='*70}")
                log(f"[{store_idx}/{len(store_names)}] {store_name}")
                log(f"{'='*70}")

                result["data"][store_name] = {}

                try:
                    # Open dropdown
                    log("  Opening dropdown...")
                    page.click(selector)
                    page.wait_for_timeout(1500)

                    # Click Stores tab
                    log("  Clicking Stores tab...")
                    page.click("text=Stores")
                    page.wait_for_timeout(1500)

                    # Find and click THIS specific store button
                    log(f"  Looking for store button: {store_name}...")

                    # Try to click the button with this exact text
                    try:
                        page.click(f"button:has-text('{store_name}')", timeout=5000)
                    except:
                        # If that doesn't work, try with menuitem role
                        page.click(f"[role='menuitem']:has-text('{store_name}')", timeout=5000)

                    page.wait_for_timeout(3000)
                    log(f"  ✓ Clicked store: {store_name}")

                    # Check if store is bound
                    page_text = page.content()
                    if "not yet been bound to a third-party platform" in page_text:
                        log(f"  ⚠ Store not bound - skipping")
                        result["data"][store_name] = "NOT_BOUND"

                        # Save progress
                        result["stores_processed"] += 1
                        with open("realtime_results.json", "w") as f:
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

                            # Scroll table left to right
                            log("      Scrolling table...")
                            page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                            page.wait_for_timeout(1000)

                            # Get all items
                            log("      Scanning items...")
                            rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")

                            items = []
                            for row in rows:
                                try:
                                    cells = row.query_selector_all("td")
                                    if len(cells) < 19:
                                        continue

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
                            with open("realtime_results.json", "w") as f:
                                json.dump(result, f, indent=2)

                        except Exception as e:
                            log(f"      ✗ Error: {e}")
                            result["data"][store_name][platform] = f"ERROR: {str(e)}"

                    # Update progress
                    result["stores_processed"] += 1
                    with open("realtime_results.json", "w") as f:
                        json.dump(result, f, indent=2)

                except Exception as e:
                    log(f"  ✗ Error processing store: {e}")
                    continue

            log("\n" + "="*70)
            log("SCRAPING COMPLETE!")
            log(f"Stores processed: {result['stores_processed']}/{result['total_stores']}")

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

    log("\n✓ Results saved to realtime_results.json")

if __name__ == "__main__":
    main()
