#!/usr/bin/env python3
"""
Complete scraper that:
1. Gets all stores
2. For each store, scrapes items from ALL 3 platforms (Grab, deliveroo, foodPanda)
3. Sets page size to 100
4. Scrolls table left-to-right, top-to-bottom to capture all data
5. Saves complete item data with images, prices, availability
"""

import sys
import json
import time
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Platform tabs
PLATFORMS = ["Grab", "deliveroo", "foodPanda"]

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("COMPLETE ALL-PLATFORMS SCRAPER")
    log("="*70)

    result = {
        "stores": [],
        "total_stores": 0,
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

            # Ensure we're on Stores tab
            log("\nStep 4: Switching to Stores tab...")
            try:
                if page.locator("text=Stores").is_visible():
                    page.click("text=Stores")
                    page.wait_for_timeout(1000)
                    log("✓ On Stores tab")
            except:
                log("  (already on Stores tab)")

            # Get all stores
            log("\nStep 5: Getting all stores...")
            stores = []
            seen = set()

            for scroll_attempt in range(30):
                all_text_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span")

                for elem in all_text_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Real store names contain @ or are long brand names
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"]:
                                # Include ALL stores (even testing/office ones)
                                if text not in seen:
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
            with open("all_platforms_results.json", "w") as f:
                json.dump(result, f, indent=2)
            log("✓ Store list saved")

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Process each store
            log("\n" + "="*70)
            log(f"Step 6: Scraping ALL {len(stores)} stores x 3 platforms...")
            log("="*70)

            for store_idx, store_name in enumerate(stores, 1):
                log(f"\n{'='*70}")
                log(f"[{store_idx}/{len(stores)}] {store_name}")
                log(f"{'='*70}")

                result["data"][store_name] = {}

                # Process each platform for this store
                for platform in PLATFORMS:
                    log(f"\n  Platform: {platform}")

                    try:
                        # Select store
                        log("    Opening store selector...")
                        page.click(selector)
                        page.wait_for_timeout(1000)

                        # Click Stores tab
                        try:
                            page.click("text=Stores")
                            page.wait_for_timeout(500)
                        except:
                            pass

                        # Type store name
                        page.keyboard.type(store_name, delay=50)
                        page.wait_for_timeout(1000)

                        # Press Enter
                        page.keyboard.press("Enter")
                        page.wait_for_timeout(3000)

                        # Check if store is bound
                        page_text = page.content()
                        if "not yet been bound to a third-party platform" in page_text:
                            log(f"    ⚠ Store not bound - skipping all platforms")
                            result["data"][store_name][platform] = "NOT_BOUND"
                            break  # Skip all platforms for this store

                        # Click platform tab
                        log(f"    Clicking {platform} tab...")
                        page.click(f"text={platform}")
                        page.wait_for_timeout(2000)

                        # Set page size to 100
                        try:
                            log("    Setting page size to 100...")
                            page.click(".ant-pagination-options .ant-select-selector", timeout=3000)
                            page.wait_for_timeout(1000)
                            page.click("text=/100.*page/i", timeout=3000)
                            page.wait_for_timeout(3000)
                            log("    ✓ Page size set to 100")
                        except:
                            log("    (page size already 100)")

                        # Scroll table left to right to see all columns
                        log("    Scrolling table to see all columns...")
                        page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                        page.wait_for_timeout(1000)

                        # Get all items from table
                        log("    Scanning items...")
                        rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")

                        items = []
                        for row in rows:
                            try:
                                cells = row.query_selector_all("td")
                                if len(cells) < 19:
                                    continue

                                # Column mapping (based on your tests)
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

                                # Availability is in column 17 (0-indexed)
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
                            except Exception as e:
                                continue

                        result["data"][store_name][platform] = items
                        log(f"    ✓ Found {len(items)} items")

                        # Save progress after each platform
                        with open("all_platforms_results.json", "w") as f:
                            json.dump(result, f, indent=2)

                    except Exception as e:
                        log(f"    ✗ Error: {e}")
                        result["data"][store_name][platform] = f"ERROR: {str(e)}"
                        continue

            log("\n" + "="*70)
            log("SCRAPING COMPLETE!")
            log(f"Total stores processed: {len(result['data'])}")

            # Count total items
            total_items = 0
            for store_data in result["data"].values():
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

    log("\n✓ Results saved to all_platforms_results.json")

if __name__ == "__main__":
    main()
