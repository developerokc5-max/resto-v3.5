#!/usr/bin/env python3
"""
FINAL WORKING VERSION
Tests each dropdown to find the correct store selector
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
    log("FINAL WORKING SCRAPER")
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

            # Find the correct store selector by testing each dropdown
            log("\nStep 3: Finding correct store selector...")
            page.wait_for_timeout(2000)

            selectors = page.query_selector_all(".ant-select-selector")
            log(f"  Found {len(selectors)} total dropdowns")

            store_selector = None
            stores = []

            # Test each selector to find which one opens store dropdown
            for idx, sel in enumerate(selectors):
                try:
                    log(f"\n  Testing selector {idx}...")

                    # Close any open dropdowns
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(300)

                    # Click this selector
                    sel.click()
                    page.wait_for_timeout(1500)

                    # Check if dropdown opened with store-like items
                    options = page.query_selector_all(".ant-select-item-option")

                    if len(options) > 0:
                        # Check first few options to see if they look like store names
                        sample_texts = []
                        for opt in options[:5]:
                            text = opt.inner_text().strip()
                            sample_texts.append(text)

                        log(f"    Found {len(options)} options: {sample_texts[:3]}")

                        # Check if any contain @ (store names) or are long brand names
                        looks_like_stores = any(
                            "@" in text or
                            any(brand in text for brand in ["HUMFULL", "OK CHICKEN", "HOKKIEN", "Le Le", "JKT"])
                            for text in sample_texts
                        )

                        if looks_like_stores:
                            log(f"    ✓ This is the STORE selector!")
                            store_selector = sel

                            # Get all stores from this dropdown
                            log("    Collecting all stores...")
                            seen = set()

                            for scroll in range(50):
                                opts = page.query_selector_all(".ant-select-item-option")
                                for opt in opts:
                                    name = opt.inner_text().strip()
                                    if name and len(name) > 5:
                                        if "testing" not in name.lower() and "office" not in name.lower():
                                            if name not in seen:
                                                stores.append(name)
                                                seen.add(name)

                                # Scroll
                                try:
                                    page.evaluate("document.querySelector('.rc-virtual-list-holder').scrollTop += 500")
                                    page.wait_for_timeout(200)
                                except:
                                    break

                            break
                        else:
                            log(f"    Not stores (probably language/settings)")
                    else:
                        log(f"    No dropdown options")

                except Exception as e:
                    log(f"    Error testing selector {idx}: {e}")
                    continue

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            if not store_selector or len(stores) == 0:
                raise Exception("Could not find store selector or no stores found")

            log(f"\n  ✓ Found {len(stores)} stores\n")
            for idx, store in enumerate(stores[:10], 1):
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

                    # Click store selector
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
