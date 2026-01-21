#!/usr/bin/env python3
"""
BULLETPROOF scraper - Works regardless of which tab was previously selected
Always ensures we're on the Stores tab before proceeding
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
    log("BULLETPROOF SCRAPER - Tab-independent")
    log("="*70)

    result = {"stores": [], "items": {}}

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
            log("\nStep 2: Navigating to page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Click the store selector dropdown (session-independent)
            log("\nStep 3: Opening dropdown...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)
            log("✓ Dropdown opened")

            # BULLETPROOF: Always ensure we're on Stores tab
            log("\nStep 4: Ensuring we're on Stores tab...")

            # Strategy 1: Try clicking by text
            try:
                if page.locator("text=Stores").is_visible():
                    page.click("text=Stores")
                    page.wait_for_timeout(1000)
                    log("✓ Clicked Stores tab (method 1: text)")
            except:
                log("  Method 1 failed, trying method 2...")

                # Strategy 2: Find tabs by structure and click the 3rd one (Stores)
                try:
                    # The tabs are in order: Group, Brands, Stores
                    # Look for the tab container
                    tabs = page.query_selector_all('.ant-dropdown [role="tab"], .ant-tabs-tab')
                    if len(tabs) >= 3:
                        tabs[2].click()  # Click 3rd tab (Stores)
                        page.wait_for_timeout(1000)
                        log("✓ Clicked Stores tab (method 2: index)")
                except:
                    log("  Method 2 failed, trying method 3...")

                    # Strategy 3: Look for any clickable element with "Stores" in it
                    try:
                        page.click('[class*="tab"]:has-text("Stores")')
                        page.wait_for_timeout(1000)
                        log("✓ Clicked Stores tab (method 3: has-text)")
                    except:
                        log("⚠ All methods failed - proceeding anyway")

            # Verify we're on Stores tab by checking if store names are visible
            page.wait_for_timeout(1000)
            dropdown_text = page.locator(".ant-dropdown").inner_text()

            if "@" in dropdown_text or "HUMFULL" in dropdown_text or "OK CHICKEN" in dropdown_text:
                log("✓ Confirmed: On Stores tab (store names visible)")
            else:
                log("⚠ Warning: May not be on Stores tab")
                log(f"  Dropdown content preview: {dropdown_text[:100]}...")

            # Get all stores
            log("\nStep 5: Getting all stores...")

            stores = []
            seen = set()

            for scroll_attempt in range(30):
                all_text_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span, .ant-dropdown button")

                count_this_round = 0
                for elem in all_text_elements:
                    try:
                        text = elem.inner_text().strip()
                        # Real store names contain @ or are long brand names
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"] and "testing" not in text.lower() and "office" not in text.lower():
                                if text not in seen:
                                    stores.append(text)
                                    seen.add(text)
                                    count_this_round += 1
                    except:
                        continue

                # Stop if no new stores found
                if len(stores) > 0 and count_this_round == 0 and scroll_attempt > 5:
                    break

                # Scroll
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

            log(f"✓ Found {len(stores)} stores")

            result["stores"] = stores
            result["total_stores"] = len(stores)

            # Save store list immediately
            with open("bulletproof_test_results.json", "w") as f:
                json.dump(result, f, indent=2)
            log("✓ Store list saved")

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Now scrape items from ALL stores
            log("\n" + "="*70)
            log(f"Step 6: Scraping items from ALL {len(stores)} stores...")
            log("="*70)

            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")

                try:
                    # Open dropdown
                    page.click(selector)
                    page.wait_for_timeout(1000)

                    # ALWAYS click Stores tab again (bulletproof)
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

                    # Check if store is bound to platform
                    page.wait_for_timeout(2000)
                    page_text = page.content()

                    if "not yet been bound to a third-party platform" in page_text or "Go Bind" in page_text:
                        log(f"  ⚠ Store not bound - skipping")
                        result["items"][store_name] = "NOT_BOUND"
                        continue

                    # Wait for table
                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
                    log(f"  ✓ Selected")

                    # Count items in table
                    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                    log(f"  ✓ Found {len(rows)} items")

                    result["items"][store_name] = len(rows)

                    # Save progress after each store
                    with open("bulletproof_test_results.json", "w") as f:
                        json.dump(result, f, indent=2)

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            log("\n" + "="*70)
            log("TEST COMPLETE!")
            log(f"Total stores: {len(stores)}")
            log(f"Tested: {len(result['items'])} stores")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

    # Save results
    with open("bulletproof_test_results.json", "w") as f:
        json.dump(result, f, indent=2)

    log("\n✓ Results saved to bulletproof_test_results.json")

if __name__ == "__main__":
    main()
