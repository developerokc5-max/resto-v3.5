#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Full Items Scraper - Scrapes ALL stores and ALL items and saves to database
"""

import sys
import io
import json
import os
import mysql.connector
from datetime import datetime
from playwright.sync_api import sync_playwright

# Fix Windows console encoding
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Database configuration from .env
DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', '3306'))
DB_DATABASE = os.getenv('DB_DATABASE', 'restodb')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')

def log(msg):
    """Log to stdout for web display"""
    print(msg, flush=True)

def connect_db():
    """Connect to MySQL database"""
    return mysql.connector.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USERNAME,
        password=DB_PASSWORD,
        database=DB_DATABASE
    )

def save_items_to_db(items_data):
    """Save scraped items to database"""
    conn = connect_db()
    cursor = conn.cursor()

    try:
        # Clear existing items (fresh sync)
        cursor.execute("DELETE FROM items WHERE platform = 'restosuite'")
        log(f"✓ Cleared old items from database")

        # Insert new items
        insert_query = """
            INSERT INTO items
            (item_id, shop_name, name, sku, category, price, image_url, is_available, platform, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """

        count = 0
        now = datetime.now()

        for item in items_data:
            cursor.execute(insert_query, (
                item.get('item_id'),
                item.get('shop_name'),
                item.get('name'),
                item.get('sku'),
                item.get('category'),
                item.get('price', 0),
                item.get('image_url'),
                item.get('is_available', True),
                'restosuite',
                now,
                now
            ))
            count += 1

        conn.commit()
        log(f"✓ Saved {count} items to database")

    except Exception as e:
        log(f"✗ Database error: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()

def scrape_item_details(page, row):
    """Extract item details from a table row"""
    try:
        # Get all cells
        cells = row.query_selector_all('td')
        if len(cells) < 5:
            return None

        # Extract data from cells
        name_cell = cells[1]  # Item name is usually in 2nd column
        category_cell = cells[2]  # Category in 3rd
        price_cell = cells[3]  # Price in 4th
        sku_cell = cells[0]  # SKU in 1st

        name = name_cell.inner_text().strip() if name_cell else ''
        category = category_cell.inner_text().strip() if category_cell else ''
        price_text = price_cell.inner_text().strip() if price_cell else '0'
        sku = sku_cell.inner_text().strip() if sku_cell else ''

        # Clean price (remove currency symbols)
        price = 0.0
        try:
            price = float(price_text.replace('$', '').replace('S', '').replace(',', '').strip())
        except:
            pass

        # Check if item is available (look for toggle/checkbox)
        is_available = True
        try:
            toggle = row.query_selector('input[type="checkbox"], .ant-switch')
            if toggle:
                is_available = toggle.is_checked() if hasattr(toggle, 'is_checked') else True
        except:
            pass

        return {
            'name': name,
            'sku': sku,
            'category': category,
            'price': price,
            'is_available': is_available,
            'image_url': None  # Will be populated if available
        }
    except Exception as e:
        log(f"    ✗ Error extracting item: {e}")
        return None

def main():
    log("="*70)
    log("FULL ITEMS SCRAPER - All Stores & Items")
    log("="*70)

    all_items = []

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\n[1/5] Logging in...")
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
            log("\n[2/5] Navigating to page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(5000)
            log("✓ Page loaded")

            # Open dropdown
            log("\n[3/5] Getting store list...")
            selector = '.flex.items-center.justify-start.cursor-pointer.rounded-md.px-\\[11px\\].py-\\[4px\\]'
            page.click(selector)
            page.wait_for_timeout(2000)

            # Ensure we're on Stores tab
            try:
                page.click("text=Stores")
                page.wait_for_timeout(1000)
            except:
                pass

            # Get all stores
            stores = []
            seen = set()

            for scroll_attempt in range(30):
                all_text_elements = page.query_selector_all(".ant-dropdown div, .ant-dropdown span, .ant-dropdown button")

                count_this_round = 0
                for elem in all_text_elements:
                    try:
                        text = elem.inner_text().strip()
                        if text and (("@" in text) or (len(text) > 15 and " " in text)):
                            if text not in ["Group", "Brands", "Stores"] and "testing" not in text.lower():
                                if text not in seen:
                                    stores.append(text)
                                    seen.add(text)
                                    count_this_round += 1
                    except:
                        continue

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

            # Close dropdown
            page.keyboard.press("Escape")
            page.wait_for_timeout(1000)

            # Scrape items from ALL stores
            log(f"\n[4/5] Scraping items from all stores...")
            log("="*70)

            for idx, store_name in enumerate(stores, 1):
                log(f"\n[{idx}/{len(stores)}] {store_name}")

                try:
                    # Open dropdown
                    page.click(selector)
                    page.wait_for_timeout(1000)

                    # Click Stores tab
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

                    # Wait for table
                    page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=10000)

                    # Get all items
                    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                    log(f"  → Found {len(rows)} items")

                    # Extract item details
                    for row in rows:
                        item_data = scrape_item_details(page, row)
                        if item_data:
                            item_data['shop_name'] = store_name
                            all_items.append(item_data)

                    log(f"  ✓ Scraped {len(rows)} items")

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    continue

            log("\n" + "="*70)
            log(f"[5/5] Saving to database...")
            save_items_to_db(all_items)

            log("\n" + "="*70)
            log("✓ SYNC COMPLETE!")
            log(f"Total stores processed: {len(stores)}")
            log(f"Total items scraped: {len(all_items)}")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Error: {e}")
            import traceback
            traceback.print_exc()

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
