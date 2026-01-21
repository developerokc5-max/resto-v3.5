#!/usr/bin/env python3
"""Quick Items Scraper - Just 5 stores for demo"""

import json
import sys
import os
import time
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"
TARGET_URL = f"{BASE_URL}/takeaway-product-mapping"

# Just scrape 5 stores for quick results
STORES_TO_SCRAPE = [
    "Le Le Mee Pok @ Toa Payoh",
    "OK CHICKEN RICE @ Bukit Batok",
    "OK CHICKEN RICE @ Tampines",
    "HUMFULL @ Bedok",
    "AH HUAT HOKKIEN MEE @ Bukit Batok",
]

def log(message):
    print(message, file=sys.stderr)

def login(page):
    log("Logging in...")
    page.goto(f"{BASE_URL}/login", wait_until="networkidle")
    page.fill("#username", EMAIL)
    page.click('button[type="submit"]')
    page.wait_for_selector("#password", timeout=10000)
    page.fill("#password", PASSWORD)
    page.click('button[type="submit"]')
    page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
    page.wait_for_timeout(2000)
    log("✓ Login successful")

def navigate_to_item_mapping(page):
    log("Navigating to Item Mapping...")
    page.goto(TARGET_URL, wait_until="networkidle")
    page.wait_for_timeout(3000)
    log("✓ Navigated")

def select_store(page, store_name):
    log(f"\nSelecting: {store_name}...")
    page.keyboard.press("Escape")
    page.wait_for_timeout(500)

    page.locator(".ant-select-selector").first.click(timeout=5000)
    page.wait_for_timeout(1000)

    page.keyboard.type(store_name, delay=50)
    page.wait_for_timeout(1500)

    page.keyboard.press("Enter")
    page.wait_for_timeout(4000)

    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
    log(f"✓ Selected: {store_name}")

def scan_items(page):
    log("Scanning items...")
    items = []

    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)
    page.wait_for_timeout(2000)

    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
    log(f"  Found {len(rows)} items")

    for idx, row in enumerate(rows, 1):
        try:
            cells = row.query_selector_all("td")
            if len(cells) < 19:
                continue

            img = cells[2].query_selector("img")
            image_url = img.get_attribute("src") if img else None

            name = cells[3].inner_text().strip()
            sku = cells[7].inner_text().strip()
            category = cells[6].inner_text().strip()

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

            if idx <= 3:
                log(f"  {idx}. {name} - ${price}")
        except:
            continue

    log(f"✓ Scanned {len(items)} items")
    return items

def main():
    log("="*60)
    log("QUICK ITEMS SCRAPER (5 stores)")
    log("="*60)

    result = {"success": False, "stores": {}, "total_items": 0}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)  # Visible for debugging
        page = browser.new_page()

        try:
            login(page)
            navigate_to_item_mapping(page)

            for store_name in STORES_TO_SCRAPE:
                log(f"\n{'='*60}")
                log(f"Store: {store_name}")
                log(f"{'='*60}")

                try:
                    select_store(page, store_name)
                    items = scan_items(page)

                    if items:
                        result["stores"][store_name] = items
                        log(f"✓ {store_name}: {len(items)} items")
                except Exception as e:
                    log(f"⚠ Error with {store_name}: {e}")
                    continue

            result["total_items"] = sum(len(items) for items in result["stores"].values())
            result["success"] = True
            result["message"] = f"Scraped {result['total_items']} items from {len(result['stores'])} stores"

            log("\n" + "="*60)
            log("SCRAPING COMPLETE!")
            log(f"Stores: {len(result['stores'])}")
            log(f"Items: {result['total_items']}")
            log("="*60)

        except Exception as e:
            log(f"\n✗ Error: {e}")
            result["message"] = str(e)
        finally:
            browser.close()

    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()
