#!/usr/bin/env python3
"""
Scroll through stores panel and click ALL stores
"""

import sys
from playwright.sync_api import sync_playwright
import time

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)

# List of ALL stores
ALL_STORES = [
    "Le Le Mee Pok",
    "Le Le Mee Pok Testing Outlet",
    "Le Le Mee Pok @ Toa Payoh",
    "JKT Western",
    "JKT Western Testing Outlet",
    "JKT Western @ Toa Payoh",
    "Drinks Stall",
    "Drinks Stall Testing Outlet",
    "51 Toa Payoh Drinks",
    "HUMFULL",
    "HUMFULL @ Taman Jurong",
    "HUMFULL Testing Outlet",
    "HUMFULL @ Edgedale Plains",
    "HUMFULL @ Punggol",
    "HUMFULL @ Marsiling",
    "HUMFULL @ Bedok",
    "HUMFULL @ Teck Whye",
    "HUMFULL @ Yishun",
    "HUMFULL @ Eunos",
    "HUMFULL @ Jurong East",
    "HUMFULL @ Hougang",
    "HUMFULL @ AMK",
    "HUMFULL @ Havelock",
    "HUMFULL @ Toa Payoh",
    "HUMFULL @ Tampines Mart",
    "HUMFULL @ Bukit Batok",
    "HUMFULL @ Lengkok Bahru",
    "HUMFULL @ Woodlands Height",
    "OK Chicken Rice",
    "OK CHICKEN RICE @ Taman Jurong",
    "OKCR Testing Outlet",
    "OK CHICKEN RICE @ Depot",
    "OK CHICKEN RICE @ Bukit Batok",
    "OK CHICKEN RICE @ Tampines",
    "OK CHICKEN RICE @ Woodlands Height",
    "OK CHICKEN RICE @ Teck Whye",
    "OK CHICKEN RICE @ Toa Payoh",
    "OK CHICKEN RICE @ Eunos",
    "OK CHICKEN RICE @ Lengkok Bahru",
    "OK CHICKEN RICE @ Punggol",
    "OK CHICKEN RICE @ Bedok",
    "OK CHICKEN RICE @ Marsiling",
    "OK CHICKEN RICE @ Jurong East",
    "OK CHICKEN RICE @ Havelock",
    "OK CHICKEN RICE @ Yishun",
    "OK CHICKEN RICE @ Hougang",
    "OK CHICKEN RICE @ AMK",
    "AH Huat Hokkien Mee",
    "AH HUAT HOKKIEN MEE @ TPY",
    "AH HUAT HOKKIEN MEE @ Bukit Batok",
    "AH HUAT HOKKIEN MEE @ PUNGGOL",
    "AH HUAT HOKKIEN PRAWN MEE ( OFFICE TESTING OUTLET )",
]

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=200)
    context = browser.new_context()
    page = context.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    # Login
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

    log("="*70)
    log(f"CLICKING ALL {len(ALL_STORES)} STORES (with scrolling)")
    log("="*70)

    success_count = 0
    fail_count = 0

    for idx, store_name in enumerate(ALL_STORES, 1):
        log(f"\n[{idx}/{len(ALL_STORES)}] {store_name}")

        max_attempts = 3
        clicked = False

        for attempt in range(max_attempts):
            try:
                # Try to click the store button
                store_button = page.get_by_text(store_name, exact=True).first

                # Scroll into view if needed
                try:
                    store_button.scroll_into_view_if_needed(timeout=2000)
                    page.wait_for_timeout(500)
                except:
                    pass

                # Click it
                store_button.click(timeout=2000)
                page.wait_for_timeout(1000)

                log(f"  ✓ CLICKED!")
                success_count += 1
                clicked = True
                break

            except Exception as e:
                if attempt < max_attempts - 1:
                    # Try scrolling the page
                    try:
                        page.evaluate("window.scrollBy(0, 300)")
                        page.wait_for_timeout(500)
                    except:
                        pass
                else:
                    log(f"  ✗ Could not click after {max_attempts} attempts")
                    fail_count += 1

        if not clicked:
            # Store not found, continue
            pass

    log("\n" + "="*70)
    log("CLICKING COMPLETE!")
    log("="*70)
    log(f"Successfully clicked: {success_count}/{len(ALL_STORES)}")
    log(f"Failed to click: {fail_count}/{len(ALL_STORES)}")
    log("="*70)

    browser.close()
