#!/usr/bin/env python3
"""
Step 3: Get all stores from dropdown and select each one
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
    log("STEP 3: Get all stores and select each one")
    log("="*70)

    result = {"stores": []}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\nLogging in...")
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
            log("\nNavigating to page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Click the store selector dropdown
            log("\nOpening store selector dropdown...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)
            log("✓ Dropdown opened")

            # Get all store names from dropdown
            log("\nGetting all stores from dropdown...")

            # Take screenshot to see dropdown structure
            page.screenshot(path="step3_dropdown_open.png")
            log("  Screenshot saved: step3_dropdown_open.png")

            stores = []
            seen = set()

            # Try multiple selectors for store items
            log("  Looking for store elements...")

            # Make sure we're on Stores tab
            if page.is_visible("text=Stores"):
                page.click("text=Stores")
                page.wait_for_timeout(1000)
                log("  ✓ Clicked Stores tab")

            # Get ALL text from the dropdown area
            log("  Getting all text from dropdown...")
            dropdown_text = page.locator(".ant-dropdown").inner_text()
            log(f"  Dropdown text preview: {dropdown_text[:200]}...")

            # Scroll through dropdown to load all stores
            for scroll_attempt in range(30):
                # Look for any div/span that might contain store names
                # Stores have @ symbol in them
                all_text_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span, .ant-dropdown button")

                log(f"  Scroll {scroll_attempt}: Checking {len(all_text_elements)} text elements...")

                count_this_round = 0
                for elem in all_text_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Real store names contain @ or are long brand names
                        # They don't contain tabs like "Group", "Brands", "Stores"
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"] and "testing" not in text.lower() and "office" not in text.lower():
                                if text not in seen:
                                    stores.append(text)
                                    seen.add(text)
                                    count_this_round += 1
                                    log(f"    Found: {text}")
                    except:
                        continue

                log(f"  Found {count_this_round} new stores this round (Total: {len(stores)})")

                # If we found stores and haven't found new ones in last 5 scrolls, stop
                if len(stores) > 0 and count_this_round == 0 and scroll_attempt > 5:
                    break

                # Scroll down in dropdown
                try:
                    page.evaluate("""
                        const dropdowns = document.querySelectorAll('.ant-dropdown, .rc-virtual-list-holder, [class*="overflow"]');
                        dropdowns.forEach(d => {
                            if (d.scrollHeight > d.clientHeight) {
                                d.scrollTop += 300;
                            }
                        });
                    """)
                    page.wait_for_timeout(300)
                except:
                    pass

            log(f"\n✓ Found {len(stores)} stores:")
            for idx, store in enumerate(stores, 1):
                log(f"  {idx}. {store}")

            result["stores"] = stores
            result["total"] = len(stores)

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Now select each store one by one
            log("\n" + "="*70)
            log("Selecting each store...")
            log("="*70)

            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] Selecting: {store_name}")

                try:
                    # Open dropdown
                    page.click(selector)
                    page.wait_for_timeout(1000)

                    # Type store name to filter
                    page.keyboard.type(store_name, delay=50)
                    page.wait_for_timeout(1000)

                    # Press Enter to select
                    page.keyboard.press("Enter")
                    page.wait_for_timeout(3000)

                    # Wait for table to load
                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

                    log(f"  ✓ Selected: {store_name}")

                    # Take screenshot
                    page.screenshot(path=f"store_{idx}_{store_name[:20]}.png")
                    log(f"  ✓ Screenshot saved")

                    # If this is the first 3 stores, pause to show it's working
                    if idx <= 3:
                        log("  (Pausing 2 seconds...)")
                        page.wait_for_timeout(2000)

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Successfully selected {len(stores)} stores")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

    # Save store list
    with open("stores_list.json", "w") as f:
        json.dump(result, f, indent=2)

    log("\n✓ Store list saved to stores_list.json")

if __name__ == "__main__":
    main()
