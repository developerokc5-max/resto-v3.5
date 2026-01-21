#!/usr/bin/env python3
"""
Scraper following SOP - Find the correct dropdown by looking for "Brands" text after clicking
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
    log("BRANDS SCRAPER - SOP Method")
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
            # Login
            log("\nStep 1: Login...")
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

            # Find the dropdown that shows Brands tab
            log("\nStep 2: Finding dropdown with Brands tab...")
            page.wait_for_timeout(2000)

            # Get all clickable elements in the top area
            all_selectors = page.query_selector_all("""
                .ant-select-selector,
                [class*="selector"],
                [class*="dropdown"],
                header [role="button"],
                .ant-layout-header .cursor-pointer
            """)

            log(f"  Found {len(all_selectors)} clickable elements, testing...")

            brands_dropdown = None

            for idx, elem in enumerate(all_selectors):
                try:
                    # Close any open dropdowns
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(300)

                    # Click this element
                    elem.click()
                    page.wait_for_timeout(1500)

                    # Check if "Brands" text appeared
                    if page.locator("text=Brands").is_visible():
                        log(f"  ✓ Found it! Element {idx} opens the Brands menu")
                        brands_dropdown = elem
                        break
                    else:
                        page.keyboard.press("Escape")
                        page.wait_for_timeout(300)

                except Exception as e:
                    continue

            if not brands_dropdown:
                # Try clicking by text pattern (organization name area)
                log("  Trying to find org dropdown by looking for organization text...")
                try:
                    # Click anywhere in top right that might be org selector
                    page.click('[class*="layout-header"] .ant-select-selector:not(:first-child)', timeout=5000)
                    page.wait_for_timeout(2000)

                    if page.locator("text=Brands").is_visible():
                        log("  ✓ Found Brands menu!")
                    else:
                        raise Exception("Could not find Brands menu")
                except:
                    raise Exception("Could not find dropdown with Brands tab")

            # Click Brands tab
            log("\nStep 3: Clicking Brands tab...")
            page.click("text=Brands")
            page.wait_for_timeout(2000)
            log("✓ Brands tab opened")

            # Get brand list
            log("\nStep 4: Getting brands...")
            brands = []

            brand_options = page.query_selector_all(".ant-select-item-option, [role='menuitem']")
            for opt in brand_options:
                text = opt.inner_text().strip()
                if text and len(text) > 2 and "testing" not in text.lower():
                    brands.append(text)
                    log(f"  - {text}")

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            log(f"\n  ✓ Found {len(brands)} brands")

            # For each brand
            for brand_idx, brand_name in enumerate(brands, 1):
                log(f"\n{'='*70}")
                log(f"BRAND {brand_idx}/{len(brands)}: {brand_name}")
                log(f"{'='*70}")

                result["brands"][brand_name] = {}

                try:
                    # Select brand
                    log(f"  Selecting '{brand_name}'...")

                    # Open dropdown again
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    if brands_dropdown:
                        brands_dropdown.click()
                    else:
                        page.click('[class*="layout-header"] .ant-select-selector:not(:first-child)')

                    page.wait_for_timeout(1000)

                    # Click Brands tab
                    page.click("text=Brands")
                    page.wait_for_timeout(1000)

                    # Type brand name
                    page.keyboard.type(brand_name, delay=50)
                    page.wait_for_timeout(1000)
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(3000)

                    log(f"  ✓ Selected '{brand_name}'")

                    # Get outlets from sidebar
                    log(f"  Getting outlets...")

                    # Expand sidebar items
                    expanders = page.query_selector_all(".ant-tree-switcher:not(.ant-tree-switcher-noop)")
                    for exp in expanders:
                        try:
                            exp.click()
                            page.wait_for_timeout(200)
                        except:
                            pass

                    page.wait_for_timeout(1000)

                    # Get outlet names
                    outlet_elems = page.query_selector_all(".ant-tree-node-content-wrapper")
                    outlets = []

                    for elem in outlet_elems:
                        text = elem.inner_text().strip()
                        if "@" in text and "testing" not in text.lower():
                            outlets.append(text)

                    log(f"  ✓ Found {len(outlets)} outlets")

                    # For each outlet
                    platforms = ["Grab", "deliveroo", "foodPanda"]

                    for outlet_idx, outlet_name in enumerate(outlets, 1):
                        log(f"\n  [{outlet_idx}/{len(outlets)}] {outlet_name}")

                        try:
                            # Click outlet in sidebar
                            page.click(f"text={outlet_name}")
                            page.wait_for_timeout(3000)

                            # Check for "Go Bind"
                            if page.is_visible("text=/Go Bind|bind it first/i"):
                                log(f"    ⚠ Not bound, skipping")
                                continue

                            result["brands"][brand_name][outlet_name] = {}

                            # For each platform
                            for platform in platforms:
                                log(f"    {platform}...")

                                try:
                                    # Click platform tab
                                    page.click(f"text={platform}")
                                    page.wait_for_timeout(2000)

                                    # Set page size to 100
                                    try:
                                        page.click(".ant-pagination-options .ant-select-selector", timeout=2000)
                                        page.wait_for_timeout(500)
                                        page.click("text=/100.*page/i")
                                        page.wait_for_timeout(2000)
                                    except:
                                        pass

                                    # Click Query if visible
                                    try:
                                        if page.is_visible("button:has-text('Query')"):
                                            page.click("button:has-text('Query')")
                                            page.wait_for_timeout(2000)
                                    except:
                                        pass

                                    # Wait for table
                                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

                                    # Scroll right
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
                                        result["brands"][brand_name][outlet_name][platform] = items
                                        result["total_items"] += len(items)
                                        log(f"      ✓ {len(items)} items")
                                    else:
                                        log(f"      ⚠ No items")

                                except Exception as e:
                                    log(f"      ✗ Error: {e}")
                                    continue

                            result["total_outlets"] += 1

                        except Exception as e:
                            log(f"    ✗ Error: {e}")
                            continue

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            result["success"] = True
            result["message"] = f"Scraped {result['total_items']} items from {result['total_outlets']} outlets"

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Brands: {len(brands)}")
            log(f"Outlets: {result['total_outlets']}")
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
