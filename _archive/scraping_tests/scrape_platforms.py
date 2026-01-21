#!/usr/bin/env python3
"""
Production Platform Scraper - Uses Playwright to scrape real data from delivery platforms
Scrapes: Grab, FoodPanda, Deliveroo with real images, prices, and availability
"""

import sys
import json
import time
from playwright.sync_api import sync_playwright
import MySQLdb
from datetime import datetime

def log(msg):
    print(msg, file=sys.stderr)

def get_db_connection():
    """Get MySQL connection from .env"""
    import os
    from dotenv import load_dotenv

    load_dotenv()

    return MySQLdb.connect(
        host=os.getenv('DB_HOST', 'localhost'),
        user=os.getenv('DB_USERNAME', 'root'),
        passwd=os.getenv('DB_PASSWORD', ''),
        db=os.getenv('DB_DATABASE', 'resto_db'),
        charset='utf8mb4'
    )

def scrape_grab_store(page, shop_id, shop_name):
    """Scrape Grab Food store"""
    log(f"  Scraping Grab: {shop_name}")
    items = []

    try:
        # Grab URL format
        url = f"https://food.grab.com/sg/en/restaurant/{shop_id}"

        log(f"    Navigating to {url}")
        page.goto(url, wait_until="networkidle", timeout=30000)
        page.wait_for_timeout(3000)

        # Wait for menu to load
        page.wait_for_selector('[class*="menuSection"], [class*="MenuItem"]', timeout=10000)

        # Get all menu items
        menu_items = page.query_selector_all('[class*="MenuItem"], [class*="menuItem"]')

        for item_elem in menu_items:
            try:
                # Extract item name
                name_elem = item_elem.query_selector('[class*="itemName"], [class*="ItemName"], h3, h4')
                name = name_elem.inner_text().strip() if name_elem else None

                if not name:
                    continue

                # Extract price
                price_elem = item_elem.query_selector('[class*="price"], [class*="Price"]')
                price_text = price_elem.inner_text().strip() if price_elem else None
                price = None
                if price_text:
                    # Extract number from "$5.50" or "S$5.50"
                    import re
                    match = re.search(r'[\d.]+', price_text)
                    if match:
                        price = float(match.group())

                # Extract image
                img_elem = item_elem.query_selector('img')
                image_url = img_elem.get_attribute('src') if img_elem else None

                # Check availability (sold out items usually have a class or overlay)
                is_available = True
                sold_out_elem = item_elem.query_selector('[class*="soldOut"], [class*="SoldOut"], [class*="unavailable"]')
                if sold_out_elem:
                    is_available = False

                items.append({
                    'name': name,
                    'price': price,
                    'image_url': image_url,
                    'is_available': is_available,
                    'platform': 'grab',
                    'category': None  # Could extract category from section headers
                })

            except Exception as e:
                log(f"      Error parsing item: {e}")
                continue

        log(f"    ✓ Found {len(items)} items")

    except Exception as e:
        log(f"    ✗ Error scraping Grab: {e}")

    return items

def scrape_foodpanda_store(page, shop_id, shop_name):
    """Scrape FoodPanda store"""
    log(f"  Scraping FoodPanda: {shop_name}")
    items = []

    try:
        # FoodPanda URL format - need to find actual store slug
        url = f"https://www.foodpanda.sg/restaurant/{shop_id}"

        log(f"    Navigating to {url}")
        page.goto(url, wait_until="networkidle", timeout=30000)
        page.wait_for_timeout(3000)

        # Wait for menu items
        page.wait_for_selector('[data-testid*="menu-product"], [class*="dish-card"]', timeout=10000)

        # Get all menu items
        menu_items = page.query_selector_all('[data-testid*="menu-product"], [class*="dish-card"]')

        for item_elem in menu_items:
            try:
                # Extract name
                name_elem = item_elem.query_selector('[data-testid="menu-product-name"], h3, h4')
                name = name_elem.inner_text().strip() if name_elem else None

                if not name:
                    continue

                # Extract price
                price_elem = item_elem.query_selector('[data-testid="menu-product-price"], [class*="price"]')
                price_text = price_elem.inner_text().strip() if price_elem else None
                price = None
                if price_text:
                    import re
                    match = re.search(r'[\d.]+', price_text)
                    if match:
                        price = float(match.group())

                # Extract image
                img_elem = item_elem.query_selector('img')
                image_url = img_elem.get_attribute('src') if img_elem else None

                # Check availability
                is_available = True
                unavailable_elem = item_elem.query_selector('[class*="unavailable"], [class*="sold-out"]')
                if unavailable_elem:
                    is_available = False

                items.append({
                    'name': name,
                    'price': price,
                    'image_url': image_url,
                    'is_available': is_available,
                    'platform': 'foodpanda',
                    'category': None
                })

            except Exception as e:
                log(f"      Error parsing item: {e}")
                continue

        log(f"    ✓ Found {len(items)} items")

    except Exception as e:
        log(f"    ✗ Error scraping FoodPanda: {e}")

    return items

def scrape_deliveroo_store(page, shop_id, shop_name):
    """Scrape Deliveroo store"""
    log(f"  Scraping Deliveroo: {shop_name}")
    items = []

    try:
        url = f"https://deliveroo.com.sg/menu/singapore/{shop_id}"

        log(f"    Navigating to {url}")
        page.goto(url, wait_until="networkidle", timeout=30000)
        page.wait_for_timeout(3000)

        # Wait for menu
        page.wait_for_selector('[data-testid*="menu-item"], [class*="MenuItem"]', timeout=10000)

        # Get all items
        menu_items = page.query_selector_all('[data-testid*="menu-item"], [class*="MenuItem"]')

        for item_elem in menu_items:
            try:
                # Extract name
                name_elem = item_elem.query_selector('h3, h4, [class*="itemName"]')
                name = name_elem.inner_text().strip() if name_elem else None

                if not name:
                    continue

                # Extract price
                price_elem = item_elem.query_selector('[class*="price"], span:has-text("$")')
                price_text = price_elem.inner_text().strip() if price_elem else None
                price = None
                if price_text:
                    import re
                    match = re.search(r'[\d.]+', price_text)
                    if match:
                        price = float(match.group())

                # Extract image
                img_elem = item_elem.query_selector('img')
                image_url = img_elem.get_attribute('src') if img_elem else None

                # Check availability
                is_available = True
                unavailable = item_elem.query_selector('[class*="unavailable"], [class*="soldOut"]')
                if unavailable:
                    is_available = False

                items.append({
                    'name': name,
                    'price': price,
                    'image_url': image_url,
                    'is_available': is_available,
                    'platform': 'deliveroo',
                    'category': None
                })

            except Exception as e:
                log(f"      Error parsing item: {e}")
                continue

        log(f"    ✓ Found {len(items)} items")

    except Exception as e:
        log(f"    ✗ Error scraping Deliveroo: {e}")

    return items

def save_items_to_db(shop_id, shop_name, items, conn):
    """Save items to database"""
    cursor = conn.cursor()

    items_inserted = 0
    history_inserted = 0

    for item in items:
        try:
            # Upsert into items table
            cursor.execute("""
                INSERT INTO items (shop_id, platform, item_name, is_available, price, image_url, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, NOW())
                ON DUPLICATE KEY UPDATE
                    is_available = VALUES(is_available),
                    price = VALUES(price),
                    image_url = VALUES(image_url),
                    updated_at = NOW()
            """, (
                shop_id,
                item['platform'],
                item['name'],
                item['is_available'],
                item['price'],
                item['image_url']
            ))
            items_inserted += 1

            # Check if status changed - insert history
            cursor.execute("""
                SELECT is_available FROM item_status_history
                WHERE shop_id = %s AND platform = %s AND item_name = %s
                ORDER BY changed_at DESC LIMIT 1
            """, (shop_id, item['platform'], item['name']))

            previous = cursor.fetchone()

            # Insert history if new or changed
            if not previous or previous[0] != item['is_available']:
                cursor.execute("""
                    INSERT INTO item_status_history
                    (item_name, shop_id, shop_name, platform, is_available, price, image_url, changed_at, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW(), NOW())
                """, (
                    item['name'],
                    shop_id,
                    shop_name,
                    item['platform'],
                    item['is_available'],
                    item['price'],
                    item['image_url']
                ))
                history_inserted += 1

        except Exception as e:
            log(f"    Error saving item '{item['name']}': {e}")

    conn.commit()
    log(f"    ✓ Saved {items_inserted} items, {history_inserted} history records")

    return items_inserted, history_inserted

def main():
    import argparse

    parser = argparse.ArgumentParser(description='Scrape delivery platform items')
    parser.add_argument('--platform', choices=['grab', 'foodpanda', 'deliveroo', 'all'], default='all')
    parser.add_argument('--shop-id', help='Specific shop ID to scrape')
    parser.add_argument('--limit', type=int, default=10, help='Number of shops to scrape')
    parser.add_argument('--headless', action='store_true', help='Run in headless mode')

    args = parser.parse_args()

    log("="*70)
    log("PLATFORM SCRAPER - Production Ready")
    log(f"Platform: {args.platform}")
    log(f"Limit: {args.limit} shops")
    log("="*70)

    # Connect to database
    try:
        conn = get_db_connection()
        log("✓ Database connected")
    except Exception as e:
        log(f"✗ Database connection failed: {e}")
        return 1

    # Get shops to scrape
    cursor = conn.cursor()

    if args.shop_id:
        cursor.execute("SELECT shop_id, shop_name FROM shops WHERE shop_id = %s", (args.shop_id,))
    else:
        cursor.execute("""
            SELECT shop_id, shop_name FROM shops
            WHERE shop_name NOT LIKE '%testing%'
            LIMIT %s
        """, (args.limit,))

    shops = cursor.fetchall()
    log(f"Found {len(shops)} shops to scrape\n")

    total_items = 0
    total_inserted = 0
    total_history = 0

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=args.headless)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        for idx, (shop_id, shop_name) in enumerate(shops, 1):
            log(f"\n[{idx}/{len(shops)}] {shop_name} ({shop_id})")

            all_items = []

            # Scrape based on platform
            if args.platform in ['grab', 'all']:
                items = scrape_grab_store(page, shop_id, shop_name)
                all_items.extend(items)

            if args.platform in ['foodpanda', 'all']:
                items = scrape_foodpanda_store(page, shop_id, shop_name)
                all_items.extend(items)

            if args.platform in ['deliveroo', 'all']:
                items = scrape_deliveroo_store(page, shop_id, shop_name)
                all_items.extend(items)

            # Save to database
            if all_items:
                inserted, history = save_items_to_db(shop_id, shop_name, all_items, conn)
                total_items += len(all_items)
                total_inserted += inserted
                total_history += history

            # Rate limiting
            time.sleep(2)

        browser.close()

    conn.close()

    log("\n" + "="*70)
    log("SCRAPING COMPLETE!")
    log(f"Total items found: {total_items}")
    log(f"Items inserted/updated: {total_inserted}")
    log(f"History records: {total_history}")
    log("="*70)

    return 0

if __name__ == "__main__":
    sys.exit(main())
