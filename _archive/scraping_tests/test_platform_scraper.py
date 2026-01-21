#!/usr/bin/env python3
"""
Test platform scraper with sample URLs
"""

from playwright.sync_api import sync_playwright
import json

def test_grab():
    """Test Grab scraping"""
    print("="*70)
    print("Testing Grab Food Scraper")
    print("="*70)

    # Example Grab URL - Replace with your actual store
    test_url = "https://food.grab.com/sg/en/restaurant/mcdonald-s-toa-payoh-delivery/1-CZQDDDET0AGANE"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        print(f"\nNavigating to: {test_url}")
        page.goto(test_url, wait_until="networkidle", timeout=60000)
        page.wait_for_timeout(5000)

        print("\nLooking for menu items...")

        # Try to find menu items with various selectors
        selectors = [
            '[data-testid*="menu-item"]',
            '[class*="MenuItem"]',
            '[class*="menuItem"]',
            'article',
            '[role="article"]'
        ]

        items_found = []

        for selector in selectors:
            elements = page.query_selector_all(selector)
            if elements:
                print(f"  ✓ Found {len(elements)} elements with selector: {selector}")

                for elem in elements[:5]:  # Get first 5 as sample
                    try:
                        # Try to get item name
                        name = None
                        for name_sel in ['h3', 'h4', '[class*="name"]', '[class*="title"]']:
                            name_elem = elem.query_selector(name_sel)
                            if name_elem:
                                name = name_elem.inner_text().strip()
                                break

                        # Try to get price
                        price = None
                        for price_sel in ['[class*="price"]', 'span:has-text("$")']:
                            price_elem = elem.query_selector(price_sel)
                            if price_elem:
                                price = price_elem.inner_text().strip()
                                break

                        # Try to get image
                        image = None
                        img_elem = elem.query_selector('img')
                        if img_elem:
                            image = img_elem.get_attribute('src') or img_elem.get_attribute('data-src')

                        if name:
                            items_found.append({
                                'name': name,
                                'price': price,
                                'image': image,
                                'selector_used': selector
                            })

                    except Exception as e:
                        print(f"    Error: {e}")

                if items_found:
                    break

        print(f"\n✓ Successfully extracted {len(items_found)} items:")
        for idx, item in enumerate(items_found[:10], 1):
            print(f"\n  Item {idx}:")
            print(f"    Name: {item['name']}")
            print(f"    Price: {item['price']}")
            print(f"    Image: {item['image'][:80] if item['image'] else 'None'}...")

        # Save page HTML for debugging
        html = page.content()
        with open('grab_page_debug.html', 'w', encoding='utf-8') as f:
            f.write(html)
        print("\n✓ Saved page HTML to grab_page_debug.html")

        # Take screenshot
        page.screenshot(path='grab_page_screenshot.png')
        print("✓ Saved screenshot to grab_page_screenshot.png")

        input("\nPress Enter to close browser...")
        browser.close()

    return items_found

def test_foodpanda():
    """Test FoodPanda scraping"""
    print("\n" + "="*70)
    print("Testing FoodPanda Scraper")
    print("="*70)

    # Example FoodPanda URL - Replace with your actual store
    test_url = "https://www.foodpanda.sg/restaurant/s7bw/mcdonald-s-toa-payoh-lor-4"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        print(f"\nNavigating to: {test_url}")
        page.goto(test_url, wait_until="networkidle", timeout=60000)
        page.wait_for_timeout(5000)

        print("\nLooking for menu items...")

        # Try various selectors
        selectors = [
            '[data-testid*="menu-product"]',
            '[data-testid*="dish"]',
            '[class*="dish-card"]',
            'article',
            '[class*="MenuItem"]'
        ]

        items_found = []

        for selector in selectors:
            elements = page.query_selector_all(selector)
            if elements:
                print(f"  ✓ Found {len(elements)} elements with selector: {selector}")

                for elem in elements[:5]:
                    try:
                        name = None
                        for name_sel in ['h3', 'h4', '[data-testid*="name"]', '[class*="name"]']:
                            name_elem = elem.query_selector(name_sel)
                            if name_elem:
                                name = name_elem.inner_text().strip()
                                break

                        price = None
                        for price_sel in ['[data-testid*="price"]', '[class*="price"]', 'span:has-text("$")']:
                            price_elem = elem.query_selector(price_sel)
                            if price_elem:
                                price = price_elem.inner_text().strip()
                                break

                        image = None
                        img_elem = elem.query_selector('img')
                        if img_elem:
                            image = img_elem.get_attribute('src') or img_elem.get_attribute('data-src')

                        if name:
                            items_found.append({
                                'name': name,
                                'price': price,
                                'image': image,
                                'selector_used': selector
                            })

                    except Exception as e:
                        print(f"    Error: {e}")

                if items_found:
                    break

        print(f"\n✓ Successfully extracted {len(items_found)} items:")
        for idx, item in enumerate(items_found[:10], 1):
            print(f"\n  Item {idx}:")
            print(f"    Name: {item['name']}")
            print(f"    Price: {item['price']}")
            print(f"    Image: {item['image'][:80] if item['image'] else 'None'}...")

        with open('foodpanda_page_debug.html', 'w', encoding='utf-8') as f:
            f.write(page.content())
        print("\n✓ Saved page HTML to foodpanda_page_debug.html")

        page.screenshot(path='foodpanda_page_screenshot.png')
        print("✓ Saved screenshot to foodpanda_page_screenshot.png")

        input("\nPress Enter to close browser...")
        browser.close()

    return items_found

if __name__ == "__main__":
    print("\nPLATFORM SCRAPER TEST")
    print("This will open browsers to test scraping")
    print("\nMake sure to update the test URLs with your actual store URLs!")
    print("\n")

    choice = input("Test which platform? (1=Grab, 2=FoodPanda, 3=Both): ")

    if choice in ['1', '3']:
        test_grab()

    if choice in ['2', '3']:
        test_foodpanda()

    print("\n" + "="*70)
    print("TEST COMPLETE")
    print("="*70)
