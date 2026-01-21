#!/usr/bin/env python3
"""
Step 2: Find and click the dropdown that opens Group/Brands/Stores
Session-independent - works regardless of what text is shown
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
    log("STEP 2: Find the correct dropdown (session-independent)")
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

            # Find the correct dropdown by testing each clickable element
            log("\nFinding dropdown with Stores tab...")
            log("Testing clickable elements...\n")

            # Get all potential clickable elements
            all_clickables = page.query_selector_all("""
                .ant-select-selector,
                [class*="select"],
                [class*="dropdown"],
                button[class*="select"]
            """)

            log(f"Found {len(all_clickables)} clickable elements")

            correct_dropdown = None
            found_index = -1

            for idx, element in enumerate(all_clickables):
                try:
                    # Close any open dropdowns
                    page.keyboard.press("Escape")
                    page.wait_for_timeout(500)

                    log(f"\nTesting element {idx}...")

                    # Click this element
                    element.click()
                    page.wait_for_timeout(1500)

                    # Check if "Stores" tab appeared
                    if page.locator("text=Stores").is_visible():
                        log(f"  ✓ SUCCESS! Element {idx} opens the Stores dropdown!")
                        correct_dropdown = element
                        found_index = idx

                        # Take screenshot
                        page.screenshot(path="step2_dropdown_opened.png")
                        log("  ✓ Screenshot saved: step2_dropdown_opened.png")

                        break
                    else:
                        log(f"  ✗ Not the right one")
                        page.keyboard.press("Escape")
                        page.wait_for_timeout(300)

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            if correct_dropdown:
                log("\n" + "="*70)
                log(f"SUCCESS! Found the dropdown at index {found_index}")
                log("Dropdown is now open showing Group/Brands/Stores tabs")
                log("="*70)
                log("\nKeeping browser open for 30 seconds...")
                time.sleep(30)
            else:
                log("\n✗ Could not find the dropdown with Stores tab")

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
