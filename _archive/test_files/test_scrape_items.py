#!/usr/bin/env python3
"""
Test Items Scraper - Test with 2 stores only
"""

import json
import sys
import os
import time
from playwright.sync_api import sync_playwright

EMAIL = os.getenv("RESTOSUITE_EMAIL", "okchickenrice2018@gmail.com")
PASSWORD = os.getenv("RESTOSUITE_PASSWORD", "90267051@Arc")
BASE_URL = "https://bo.sea.restosuite.ai"
TARGET_URL = f"{BASE_URL}/takeaway-product-mapping"

def log(message):
    print(message, file=sys.stderr)

def login(page):
    log("Logging in...")
    try:
        page.goto(f"{BASE_URL}/login", wait_until="networkidle")
        page.wait_for_selector("#username", timeout=10000)
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_selector("#password", timeout=10000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
        page.wait_for_timeout(2000)
        log("✓ Login successful")
        return True
    except Exception as e:
        log(f"✗ Login failed: {e}")
        return False

def navigate_to_item_mapping(page):
    log("Navigating to Item Mapping page...")
    try:
        page.goto(TARGET_URL, wait_until="networkidle")
        page.wait_for_timeout(3000)
        log("✓ Navigated to Item Mapping page")
        return True
    except Exception as e:
        log(f"✗ Navigation failed: {e}")
        return False

def select_store(page, store_name):
    log(f"\nSelecting store: {store_name}...")
    try:
        # Clear any existing search
        page.keyboard.press("Escape")
        page.wait_for_timeout(500)

        # Click store dropdown
        try:
            page.locator(".ant-select-selector").first.click(timeout=5000)
            page.wait_for_timeout(1000)
        except:
            try:
                page.locator("input[type='search']").first.click(timeout=3000)
                page.wait_for_timeout(1000)
            except Exception as e2:
                log(f"  Could not click dropdown: {e2}")
                return False

        # Type store name to search
        page.keyboard.type(store_name, delay=50)
        page.wait_for_timeout(1500)

        # Press Enter to select
        page.keyboard.press("Enter")
        page.wait_for_timeout(3000)

        # Verify table loaded
        page.wait_for_selector("table tbody tr", timeout=5000)

        log(f"✓ Selected store: {store_name}")
        return True
    except Exception as e:
        log(f"⚠ Could not select store: {e}")
        return False

def scan_items_table(page):
    log("\nScanning items table...")
    items_data = []

    try:
        page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
        page.wait_for_timeout(2000)

        rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
        log(f"  Found {len(rows)} item rows")

        for idx, row in enumerate(rows, 1):
            try:
                cells = row.query_selector_all("td")
                if len(cells) < 19:
                    continue

                # Extract item image URL (column 2)
                image_url = None
                img_element = cells[2].query_selector("img")
                if img_element:
                    image_url = img_element.get_attribute("src")

                # Extract item name (column 3)
                item_name = cells[3].inner_text().strip() if cells[3] else ""

                # Extract size/unit (column 4)
                item_size = cells[4].inner_text().strip() if cells[4] else "-"

                # Extract category (column 6)
                item_category = cells[6].inner_text().strip() if cells[6] else "-"

                # Extract SKU (column 7)
                item_sku = cells[7].inner_text().strip() if cells[7] else ""

                # Extract price (column 8)
                price_text = cells[8].inner_text().strip() if cells[8] else "0"
                try:
                    price_clean = price_text.replace("S$", "").replace("$", "").strip()
                    item_price = float(price_clean) if price_clean else 0.0
                except:
                    item_price = 0.0

                # Extract listing status (column 17)
                status_text = cells[17].inner_text().strip().lower()
                is_listed = "listed" in status_text

                item_data = {
                    "name": item_name,
                    "image_url": image_url,
                    "size": item_size,
                    "category": item_category,
                    "sku": item_sku,
                    "price": item_price,
                    "is_available": is_listed,
                    "status": "Listed" if is_listed else "Unlisted"
                }

                items_data.append(item_data)
                log(f"  {idx}. {item_name} - ${item_price} - {item_data['status']}")

            except Exception as e:
                log(f"  ⚠ Error processing row {idx}: {e}")
                continue

        log(f"\n✓ Scanned {len(items_data)} items")
        return items_data

    except Exception as e:
        log(f"✗ Error scanning items table: {e}")
        return []

def main():
    log("="*60)
    log("TEST ITEMS SCRAPER (2 stores only)")
    log("="*60)

    test_stores = [
        "Le Le Mee Pok @ Toa Payoh",
        "OK CHICKEN RICE @ Bukit Batok",
    ]

    result = {
        "success": False,
        "stores": {},
        "total_items": 0,
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1920, "height": 1080})
        page = context.new_page()

        try:
            if not login(page):
                result["message"] = "Login failed"
                print(json.dumps(result))
                return

            if not navigate_to_item_mapping(page):
                result["message"] = "Navigation failed"
                print(json.dumps(result))
                return

            for store_name in test_stores:
                log(f"\n{'='*60}")
                log(f"Processing: {store_name}")
                log(f"{'='*60}")

                if not select_store(page, store_name):
                    log(f"  Skipping {store_name}")
                    continue

                items = scan_items_table(page)

                if items:
                    result["stores"][store_name] = items
                    log(f"✓ {store_name}: {len(items)} items scraped")
                else:
                    log(f"⚠ {store_name}: No items found")

            result["total_items"] = sum(len(items) for items in result["stores"].values())
            result["success"] = True
            result["message"] = f"Test complete: {result['total_items']} items from {len(result['stores'])} stores"

            log("\n" + "="*60)
            log("TEST COMPLETE!")
            log("="*60)
            log(f"Stores: {len(result['stores'])}")
            log(f"Items: {result['total_items']}")

        except Exception as e:
            log(f"\n✗ Fatal error: {e}")
            result["message"] = f"Error: {str(e)}"

        finally:
            browser.close()

    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()
