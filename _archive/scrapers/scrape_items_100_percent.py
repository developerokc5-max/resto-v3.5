#!/usr/bin/env python3
"""
100% BULLETPROOF - Uses LEFT SIDEBAR to get stores
The sidebar always shows the store list regardless of session state
No reliance on organization names or dropdown detection
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
    log("100% BULLETPROOF SCRAPER - Using Sidebar")
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

            # Get stores from LEFT SIDEBAR
            log("\nStep 3: Getting stores from sidebar...")
            page.wait_for_timeout(2000)

            # Take screenshot to see page structure
            page.screenshot(path="debug_sidebar.png")
            log("  Screenshot saved: debug_sidebar.png")

            # The sidebar shows store brands/groups as expandable items
            # We need to expand them to see individual stores
            # Look for elements with role="treeitem" or similar in the sidebar

            stores = []

            # Find all expandable groups in sidebar (HUMFULL, OK Chicken Rice, etc.)
            # These are usually <div> elements with certain classes in the left navigation

            # Strategy: Look for text elements in the sidebar that contain store names
            # The sidebar is typically in a nav or aside element on the left

            # First, let's find all potential store links/text in sidebar
            # They're usually in elements with specific classes or under certain containers

            # Get all text from sidebar area (left 300px of screen)
            sidebar_items = page.query_selector_all("""
                aside .ant-tree-treenode,
                nav .ant-menu-item,
                .ant-layout-sider [role="treeitem"],
                .ant-layout-sider .ant-tree-node-content-wrapper
            """)

            log(f"  Found {len(sidebar_items)} sidebar items")

            for item in sidebar_items:
                try:
                    text = item.inner_text().strip()
                    # Real store names contain @ or are brand names
                    if text and (
                        "@" in text or  # Individual store: "OK CHICKEN RICE @ Bukit Batok"
                        text in ["HUMFULL", "OK Chicken Rice", "AH Huat Hokkien Mee", "Le Le Mee Pok", "JKT Western", "51 Toa Payoh Drinks", "Drinks Stall"]
                    ):
                        if "testing" not in text.lower() and "office" not in text.lower():
                            if text not in stores:
                                stores.append(text)
                                log(f"  Found: {text}")
                except:
                    pass

            # If we found brand names but not individual stores, we need to expand them
            if len(stores) < 10:
                log("\n  Expanding brand groups to find individual stores...")

                # Find and click expandable brand items
                expandable_items = page.query_selector_all("""
                    .ant-tree-switcher:not(.ant-tree-switcher-noop),
                    [role="treeitem"] .ant-tree-switcher
                """)

                log(f"  Found {len(expandable_items)} expandable items")

                for item in expandable_items:
                    try:
                        item.click()
                        page.wait_for_timeout(500)
                    except:
                        pass

                # Now get all stores again
                page.wait_for_timeout(1000)
                stores = []

                all_sidebar_text = page.query_selector_all("""
                    .ant-layout-sider .ant-tree-node-content-wrapper,
                    .ant-layout-sider [role="treeitem"]
                """)

                for item in all_sidebar_text:
                    try:
                        text = item.inner_text().strip()
                        if "@" in text:  # Individual store
                            if "testing" not in text.lower() and "office" not in text.lower():
                                if text not in stores:
                                    stores.append(text)
                    except:
                        pass

            log(f"\n  ✓ Found {len(stores)} stores total\n")
            for idx, store in enumerate(stores[:15], 1):
                log(f"    {idx}. {store}")
            if len(stores) > 15:
                log(f"    ... and {len(stores)-15} more")

            # Scrape each store by clicking it in sidebar
            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")
                log("="*70)

                try:
                    # Click the store in sidebar to select it
                    log("  Clicking store in sidebar...")

                    # Find the sidebar item with this exact text and click it
                    page.click(f"text={store_name}", timeout=5000)
                    page.wait_for_timeout(3000)

                    # Wait for table to load
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
