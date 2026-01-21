#!/usr/bin/env python3
"""
Step 2: Click the CORRECT element - the store selector with specific classes
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
    log("STEP 2: Click the correct store selector element")
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

            # Navigate
            log("\nNavigating to page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Find and click the EXACT element with those specific classes
            log("\nFinding the store selector element...")
            log("Looking for: .flex.items-center.justify-start.cursor-pointer.rounded-md")

            # Build the CSS selector based on the classes you showed
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'

            # Check if element exists
            if page.is_visible(selector):
                log(f"✓ Found the element!")

                # Take screenshot before click
                page.screenshot(path="step2_before_click.png")
                log("  Screenshot saved: step2_before_click.png")

                # Click it
                log("\nClicking the element...")
                page.click(selector)
                page.wait_for_timeout(2000)

                # Take screenshot after click
                page.screenshot(path="step2_after_click.png")
                log("  Screenshot saved: step2_after_click.png")

                # Check if dropdown opened (should show Group/Brands/Stores tabs)
                if page.is_visible("text=Stores"):
                    log("\n✓ SUCCESS! Dropdown opened with Stores tab visible!")
                elif page.is_visible("text=Brands"):
                    log("\n✓ SUCCESS! Dropdown opened with Brands tab visible!")
                else:
                    log("\n⚠ Dropdown may have opened but tabs not visible")

                log("\n" + "="*70)
                log("SUCCESS! Found and clicked the correct element")
                log("="*70)

                # Keep browser open
                log("\nKeeping browser open for 30 seconds...")
                time.sleep(30)

            else:
                log(f"✗ Element not found with selector: {selector}")
                log("\nTrying alternative selector...")

                # Alternative: find by the combination of classes without exact padding values
                alt_selector = '.cursor-pointer.rounded-md .anticon-down'
                if page.is_visible(alt_selector):
                    log("✓ Found using alternative selector (parent of down arrow)")
                    parent = page.locator(alt_selector).locator('xpath=ancestor::div[contains(@class, "cursor-pointer")]').first
                    parent.click()
                    page.wait_for_timeout(2000)
                    log("✓ Clicked!")
                else:
                    log("✗ Could not find element with alternative selector either")

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
