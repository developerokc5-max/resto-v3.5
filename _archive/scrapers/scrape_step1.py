#!/usr/bin/env python3
"""
Step 1: Login and navigate to the page
Then STOP and wait for next instruction
"""

import sys
from playwright.sync_api import sync_playwright
import time

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

def main():
    log("="*70)
    log("STEP 1: Login and Navigate")
    log("="*70)

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

            # Navigate to takeaway-product-mapping
            log("\nNavigating to takeaway-product-mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Take screenshot
            page.screenshot(path="step1_page_loaded.png")
            log("✓ Screenshot saved: step1_page_loaded.png")

            log("\n" + "="*70)
            log("STEP 1 COMPLETE - Waiting for next instruction...")
            log("Browser will stay open for 60 seconds")
            log("="*70)

            # Keep browser open
            time.sleep(60)

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
