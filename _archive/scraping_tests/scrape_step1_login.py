#!/usr/bin/env python3
"""
Step 1: Login and navigate to the page
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
    log("STEP 1: LOGIN AND NAVIGATE")
    log("=" * 70)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\n[1] Going to login page...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(2000)

            log("[2] Filling username...")
            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')
            page.wait_for_timeout(2000)

            log("[3] Filling password...")
            page.wait_for_selector("#password", timeout=30000)
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')
            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(3000)
            log("[OK] Logged in successfully")

            # Navigate to target page
            log("\n[4] Going to takeaway-product-mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
            page.wait_for_timeout(5000)
            log("[OK] Page loaded")

            # Make sure we're on the GROUP tab
            log("\n[5] Ensuring we're on the GROUP tab...")
            try:
                # Click the organization dropdown to open it
                log("  Opening organization dropdown...")
                page.click("text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD", timeout=10000)
                page.wait_for_timeout(2000)

                # Click the "Group" tab to make sure we're on it
                log("  Clicking Group tab...")
                page.click("text=Group", timeout=5000)
                page.wait_for_timeout(2000)

                # Close the dropdown by clicking somewhere else
                page.keyboard.press("Escape")
                page.wait_for_timeout(1000)
                log("[OK] Now on GROUP tab")
            except Exception as e:
                log(f"  [WARNING] Could not switch to Group tab: {e}")

            log("\n" + "=" * 70)
            log("SUCCESS - Ready to scrape")
            log("=" * 70)

            # Wait so you can see the page
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
