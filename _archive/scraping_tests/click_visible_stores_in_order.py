#!/usr/bin/env python3
import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=500)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("Logging in...")
    page.goto(f"{BASE_URL}/takeaway-product-mapping")
    page.wait_for_timeout(5000)

    if "/login" in page.url:
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_timeout(2000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_timeout(5000)
        page.goto(f"{BASE_URL}/takeaway-product-mapping")
        page.wait_for_timeout(5000)

    log("✓ Logged in\n")

    # Find the scrollable container with stores
    log("Looking for stores in the overflow panel...")
    
    # Get the scrollable panel
    scroll_panel = page.query_selector('[class*="overflow-y-auto"]')
    
    if not scroll_panel:
        log("Could not find scrollable panel")
        browser.close()
        sys.exit(1)
    
    log("✓ Found scrollable stores panel\n")
    
    # Collect all store elements by scrolling through the panel
    clicked_stores = []
    last_count = 0
    scroll_attempts = 0
    max_scrolls = 30
    
    log("="*70)
    log("CLICKING ALL STORES")
    log("="*70)
    
    while scroll_attempts < max_scrolls:
        # Find all clickable elements in the panel that look like store names
        # Looking for elements with @ symbol
        all_elements = page.query_selector_all('[class*="overflow-y-auto"] *')
        
        for elem in all_elements:
            try:
                text = elem.inner_text().strip()
                # Check if it looks like a store name
                if "@" in text and 10 < len(text) < 60 and text not in clicked_stores:
                    # Try to click it
                    try:
                        elem.scroll_into_view_if_needed()
                        page.wait_for_timeout(300)
                        elem.click(timeout=2000)
                        page.wait_for_timeout(1000)
                        
                        clicked_stores.append(text)
                        log(f"[{len(clicked_stores)}] ✓ CLICKED: {text}")
                    except:
                        pass
            except:
                pass
        
        # Scroll the panel
        try:
            scroll_panel.evaluate("el => el.scrollTop += 300")
            page.wait_for_timeout(500)
        except:
            pass
        
        # Check if we found new stores
        if len(clicked_stores) == last_count:
            scroll_attempts += 1
        else:
            scroll_attempts = 0
            last_count = len(clicked_stores)
    
    log("\n" + "="*70)
    log("CLICKING COMPLETE!")
    log("="*70)
    log(f"Total stores clicked: {len(clicked_stores)}")
    log("="*70)
    
    # List all clicked stores
    log("\nStores clicked:")
    for store in clicked_stores:
        log(f"  - {store}")
    
    browser.close()
