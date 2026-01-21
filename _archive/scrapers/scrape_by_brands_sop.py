#!/usr/bin/env python3
"""
RestoSuite Items Scraper - Following Official SOP
Uses Brands view to scrape all outlets across all 3 platforms
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
    log("SCRAPER - Following Official SOP (Brands View)")
    log("="*70)

    result = {
        "success": False,
        "brands": {},
        "total_items": 0,
        "total_outlets": 0
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Step 1: Login
            log("\nStep 1: Login + open correct page...")
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

            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ On Item Mapping page")

            # Step 2: Switch to Brands view
            log("\nStep 2: Switching to Brands view...")

            # Find all selectors and test each one
            selectors = page.query_selector_all(".ant-select-selector")
            log(f"  Found {len(selectors)} dropdowns, testing each...")

            brands_opened = False
            for idx, sel in enumerate(selectors):
                try:
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(300)

                    sel.click()
                    page.wait_for_timeout(1500)

                    # Check if this opened the Group/Brands/Stores popup
                    if page.is_visible("text=Brands"):
                        log(f"  ✓ Found the org selector (dropdown {idx})")
                        page.click("text=Brands", timeout=3000)
                        page.wait_for_timeout(2000)
                        log("  ✓ Clicked 'Brands' tab")
                        brands_opened = True
                        break
                    else:
                        page.keyboard.press("Escape")
                        page.wait_for_timeout(300)
                except:
                    continue

            if not brands_opened:
                raise Exception("Could not find Brands tab")

            # Get the 6 brand buttons
            log("\nStep 3: Getting brand list...")
            page.wait_for_timeout(2000)

            # Brands are shown as options in the dropdown after clicking Brands tab
            brand_options = page.query_selector_all(".ant-select-item-option, .ant-dropdown [role='menuitem']")

            brands = []
            for opt in brand_options:
                text = opt.inner_text().strip()
                if text and len(text) > 3:
                    brands.append(text)

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            log(f"  ✓ Found {len(brands)} brands:")
            for idx, brand in enumerate(brands, 1):
                log(f"    {idx}. {brand}")

            # Step 3: For each brand
            for brand_idx, brand_name in enumerate(brands, 1):
                log(f"\n{'='*70}")
                log(f"BRAND {brand_idx}/{len(brands)}: {brand_name}")
                log(f"{'='*70}")

                result["brands"][brand_name] = {}

                try:
                    # A) Select the brand
                    log(f"\n  Selecting brand '{brand_name}'...")

                    # Click org/brand selector again
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    # Find and click the selector that opens Group/Brands/Stores
                    for sel in selectors:
                        try:
                            sel.click()
                            page.wait_for_timeout(1000)
                            if page.is_visible("text=Brands"):
                                page.click("text=Brands", timeout=2000)
                                page.wait_for_timeout(1000)
                                break
                        except:
                            continue

                    # Type brand name and select
                    page.keyboard.type(brand_name, delay=50)
                    page.wait_for_timeout(1000)
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(3000)

                    log(f"  ✓ Brand '{brand_name}' selected")

                    # B) Get outlets from left sidebar
                    log(f"\n  Getting outlets from left sidebar...")
                    page.wait_for_timeout(2000)

                    # Expand all outlets in sidebar
                    expandable = page.query_selector_all(".ant-tree-switcher:not(.ant-tree-switcher-noop)")
                    for exp in expandable:
                        try:
                            exp.click()
                            page.wait_for_timeout(300)
                        except:
                            pass

                    page.wait_for_timeout(1000)

                    # Get all outlet names from sidebar
                    outlet_elements = page.query_selector_all(".ant-tree-node-content-wrapper")
                    outlets = []

                    for elem in outlet_elements:
                        text = elem.inner_text().strip()
                        # Outlets contain @ symbol
                        if "@" in text and "testing" not in text.lower():
                            outlets.append(text)

                    log(f"  ✓ Found {len(outlets)} outlets for {brand_name}")

                    # C & D) For each outlet, scrape all 3 platforms
                    platforms = ["Grab", "deliveroo", "foodPanda"]

                    for outlet_idx, outlet_name in enumerate(outlets, 1):
                        log(f"\n  [{outlet_idx}/{len(outlets)}] {outlet_name}")

                        result["brands"][brand_name][outlet_name] = {}

                        # Click outlet in sidebar
                        try:
                            page.click(f"text={outlet_name}", timeout=5000)
                            page.wait_for_timeout(3000)
                        except Exception as e:
                            log(f"    ✗ Could not select outlet: {e}")
                            continue

                        # Check if "Go Bind" message appears
                        if page.is_visible("text=/Go Bind|bind it first/i"):
                            log(f"    ⚠ Outlet not bound, skipping")
                            continue

                        # For each platform tab
                        for platform in platforms:
                            log(f"    Platform: {platform}")

                            try:
                                # Click platform tab
                                page.click(f"text={platform}", timeout=3000)
                                page.wait_for_timeout(2000)

                                # Set page size to 100
                                try:
                                    page.click(".ant-pagination-options .ant-select-selector", timeout=2000)
                                    page.wait_for_timeout(500)
                                    page.click("text=/100.*page/i", timeout=2000)
                                    page.wait_for_timeout(2000)
                                except:
                                    pass

                                # Click Query if needed
                                try:
                                    if page.is_visible("button:has-text('Query')"):
                                        page.click("button:has-text('Query')", timeout=2000)
                                        page.wait_for_timeout(2000)
                                except:
                                    pass

                                # Wait for table to load
                                page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

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

                                        # Get data
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

                                        # MOST IMPORTANT: Listing status (column 17)
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
                                            "status": "Listed" if is_listed else "Unlisted"
                                        })
                                    except:
                                        continue

                                if items:
                                    if outlet_name not in result["brands"][brand_name]:
                                        result["brands"][brand_name][outlet_name] = {}

                                    result["brands"][brand_name][outlet_name][platform] = items
                                    result["total_items"] += len(items)
                                    log(f"      ✓ {len(items)} items")
                                else:
                                    log(f"      ⚠ No items")

                            except Exception as e:
                                log(f"      ✗ Error on {platform}: {e}")
                                continue

                        result["total_outlets"] += 1

                except Exception as e:
                    log(f"  ✗ Error processing brand {brand_name}: {e}")
                    continue

            result["success"] = True
            result["message"] = f"Scraped {result['total_items']} items from {result['total_outlets']} outlets across {len(brands)} brands"

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Brands: {len(brands)}")
            log(f"Outlets: {result['total_outlets']}")
            log(f"Total Items: {result['total_items']}")
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
