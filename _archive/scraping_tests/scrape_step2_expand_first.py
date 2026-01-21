#!/usr/bin/env python3
"""
Step 2: Expand the first group in the sidebar tree
"""

import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr, flush=True)

def main():
    log("=" * 70)
    log("STEP 2: EXPAND FIRST GROUP")
    log("=" * 70)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\n[1] Logging in...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(2000)
            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')
            page.wait_for_timeout(2000)
            page.wait_for_selector("#password", timeout=30000)
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')
            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(3000)
            log("[OK] Logged in")

            # Navigate to page
            log("\n[2] Going to takeaway-product-mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
            page.wait_for_timeout(5000)
            log("[OK] Page loaded")

            # Make sure we're on GROUP tab
            log("\n[3] Ensuring we're on GROUP tab...")
            try:
                page.click("text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD", timeout=10000)
                page.wait_for_timeout(2000)
                page.click("text=Group", timeout=5000)
                page.wait_for_timeout(2000)
                page.keyboard.press("Escape")
                page.wait_for_timeout(1000)
                log("[OK] On GROUP tab")
            except Exception as e:
                log(f"  [WARNING] Could not switch to Group tab: {e}")

            # Find the first group tree node
            log("\n[4] Finding first group in tree...")
            page.wait_for_selector(".ant-tree", timeout=10000)
            page.wait_for_timeout(2000)

            # Get the first tree switcher (the arrow icon)
            # The class is "ant-tree-switcher" with "ant-tree-switcher_open" or "ant-tree-switcher_close"
            first_switcher = page.locator(".ant-tree-switcher").first

            # Check if it's already open or closed
            switcher_class = first_switcher.get_attribute("class")
            log(f"  First switcher class: {switcher_class}")

            if "ant-tree-switcher_close" in switcher_class:
                log("  First group is CLOSED, clicking to OPEN...")
                first_switcher.click()
                page.wait_for_timeout(2000)
                log("  [OK] Clicked arrow to open")
            elif "ant-tree-switcher_open" in switcher_class:
                log("  First group is already OPEN")
                log("  Closing it first...")
                first_switcher.click()
                page.wait_for_timeout(1000)
                log("  Now opening it again...")
                first_switcher.click()
                page.wait_for_timeout(2000)
                log("  [OK] Toggled open")
            else:
                log(f"  [WARNING] Unknown switcher state: {switcher_class}")

            # Verify it's now open
            switcher_class_after = first_switcher.get_attribute("class")
            log(f"  After clicking, class: {switcher_class_after}")

            if "ant-tree-switcher_open" in switcher_class_after:
                log("[SUCCESS] First group is now OPEN!")
            else:
                log("[WARNING] First group might not be open")

            log("\n" + "=" * 70)
            log("STEP 2 COMPLETE")
            log("=" * 70)

            # Wait so you can see
            log("\nBrowser will stay open for 30 seconds...")
            page.wait_for_timeout(30000)

        except Exception as e:
            log(f"\n[ERROR] {e}")
            import traceback
            traceback.print_exc(file=sys.stderr)

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
