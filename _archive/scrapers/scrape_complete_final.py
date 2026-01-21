#!/usr/bin/env python3
"""
Complete scraper - Click each store button, scrape items, skip if "Go Bind"
"""

import sys
import json
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("COMPLETE SCRAPER - Click store buttons one by one")
    log("="*70)

    result = {"success": False, "items": {}, "total_items": 0, "stores_skipped": []}

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
            log("\nStep 2: Navigating...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Open dropdown
            log("\nStep 3: Opening dropdown...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)
            log("✓ Dropdown opened")

            # Click Stores tab
            log("\nStep 4: Clicking Stores tab...")
            page.click("text=Stores")
            page.wait_for_timeout(1000)
            log("✓ On Stores tab")

            # Get all store names (just text, we'll click by text later)
            log("\nStep 5: Getting all store names...")

            stores_to_click = []
            seen = set()

            # Scroll to load all stores
            for i in range(30):
                # Get all text elements
                all_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span")

                for elem in all_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Only real stores with @ or long names
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"] and "testing" not in text.lower() and "office" not in text.lower():
                                if text not in seen:
                                    stores_to_click.append(text)
                                    seen.add(text)
                    except:
                        continue

                # Scroll
                try:
                    page.evaluate("""
                        const dropdowns = document.querySelectorAll('.ant-dropdown, .rc-virtual-list-holder');
                        dropdowns.forEach(d => {
                            if (d.scrollHeight > d.clientHeight) d.scrollTop += 300;
                        });
                    """)
                    page.wait_for_timeout(200)
                except:
                    pass

            # Sort stores alphabetically
            stores_to_click.sort()
            log(f"✓ Found {len(stores_to_click)} stores (sorted A-Z)")

            # Now click each store button one by one
            log("\n" + "="*70)
            log("Step 6: Clicking each store and scanning all platforms...")
            log("="*70)

            for idx, store_name in enumerate(stores_to_click, 1):
                log(f"\n[{idx}/{len(stores_to_click)}] {store_name}")

                try:
                    # Re-open dropdown if needed
                    if not page.is_visible(".ant-dropdown"):
                        log("  Re-opening dropdown...")
                        page.click(selector)
                        page.wait_for_timeout(1000)
                        page.click("text=Stores")
                        page.wait_for_timeout(1000)

                    # Click the store by its exact text in the dropdown
                    page.locator(".ant-dropdown").locator(f"text={store_name}").first.click()
                    page.wait_for_timeout(3000)
                    log(f"  ✓ Clicked store")

                    # Check for "Go Bind" button
                    if page.is_visible("text=/Go Bind|bind it first/i"):
                        log(f"  ⚠ Store not bound - SKIPPING")
                        result["stores_skipped"].append(store_name)
                        continue

                    # Wait for table to load
                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

                    # Scan all 3 platforms and set each to 100 items per page
                    platforms = ["Grab", "foodPanda", "deliveroo"]
                    store_items = {}

                    for platform in platforms:
                        log(f"    {platform}...")

                        try:
                            # Click platform tab
                            page.click(f"text={platform}")
                            page.wait_for_timeout(2000)

                            # Wait for table
                            page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

                            # Set page size to 100
                            try:
                                page.click(".ant-pagination-options .ant-select-selector", timeout=2000)
                                page.wait_for_timeout(500)
                                page.click("text=/100.*page/i")
                                page.wait_for_timeout(2000)
                                log(f"      ✓ Set page size to 100")
                            except:
                                log(f"      ⚠ Could not set page size")

                            # Scroll right to see all columns
                            page.evaluate("document.querySelector('.ant-table-body').scrollLeft = 5000")
                            page.wait_for_timeout(1000)

                            # Scan items
                            rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                            items = []

                            for row in rows:
                                try:
                                    cells = row.query_selector_all("td")
                                    if len(cells) < 19:
                                        continue

                                    # Get item data
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
                                        "platform": platform.lower(),
                                        "is_available": is_listed,
                                    })
                                except:
                                    continue

                            if items:
                                store_items[platform] = items
                                result["total_items"] += len(items)
                                log(f"      ✓ {len(items)} items")
                            else:
                                log(f"      ⚠ No items")

                        except Exception as e:
                            log(f"      ✗ Error: {e}")
                            continue

                    # Save store data
                    if store_items:
                        result["items"][store_name] = store_items
                        total_for_store = sum(len(items) for items in store_items.values())
                        log(f"  ✓ Total: {total_for_store} items across {len(store_items)} platforms")
                    else:
                        log(f"  ⚠ No items found for this store")

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            result["success"] = True
            result["total_stores_found"] = len(stores_to_click)
            result["total_stores_scraped"] = len(result["items"])
            result["total_skipped"] = len(result["stores_skipped"])
            result["message"] = f"Scraped {result['total_items']} items from {result['total_stores_scraped']} stores"

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Total stores found: {result['total_stores_found']}")
            log(f"Stores scraped: {result['total_stores_scraped']}")
            log(f"Stores skipped: {result['total_skipped']}")
            log(f"Total items: {result['total_items']}")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Fatal error: {e}")
            result["message"] = str(e)
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

    # Save results
    with open("items_complete.json", "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2, ensure_ascii=False)

    log("\n✓ Results saved to items_complete.json")

if __name__ == "__main__":
    main()
