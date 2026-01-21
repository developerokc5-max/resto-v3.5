#!/usr/bin/env python3
"""
FINAL VERSION: Click all stores one by one
"""
import sys, time
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr)
    sys.stderr.flush()

stores_to_click = [
    "HUMFULL @ Taman Jurong", "OK CHICKEN RICE @ Taman Jurong",
    "Le Le Mee Pok Testing Outlet", "JKT Western Testing Outlet",
    "Drinks Stall Testing Outlet", "AH HUAT HOKKIEN MEE @ TPY",
    "Le Le Mee Pok @ Toa Payoh", "JKT Western @ Toa Payoh",
    "51 Toa Payoh Drinks", "AH HUAT HOKKIEN MEE @ Bukit Batok",
    "HUMFULL Testing Outlet", "HUMFULL @ Edgedale Plains",
    "HUMFULL @ Punggol", "HUMFULL @ Marsiling", "HUMFULL @ Bedok",
    "HUMFULL @ Teck Whye", "HUMFULL @ Yishun", "HUMFULL @ Eunos",
    "HUMFULL @ Jurong East", "HUMFULL @ Hougang", "HUMFULL @ AMK",
    "HUMFULL @ Havelock", "HUMFULL @ Toa Payoh",
    "HUMFULL @ Tampines Mart", "HUMFULL @ Bukit Batok",
    "HUMFULL @ Lengkok Bahru", "HUMFULL @ Woodlands Height",
    "OKCR Testing Outlet", "OK CHICKEN RICE @ Depot",
    "OK CHICKEN RICE @ Bukit Batok", "OK CHICKEN RICE @ Tampines",
    "OK CHICKEN RICE @ Woodlands Height", "OK CHICKEN RICE @ Teck Whye",
    "OK CHICKEN RICE @ Toa Payoh", "OK CHICKEN RICE @ Eunos",
    "OK CHICKEN RICE @ Lengkok Bahru", "OK CHICKEN RICE @ Punggol",
    "OK CHICKEN RICE @ Bedok", "OK CHICKEN RICE @ Marsiling",
    "OK CHICKEN RICE @ Jurong East", "OK CHICKEN RICE @ Havelock",
    "OK CHICKEN RICE @ Yishun", "OK CHICKEN RICE @ Hougang",
    "OK CHICKEN RICE @ AMK", "AH HUAT HOKKIEN MEE @ PUNGGOL",
    "AH HUAT HOKKIEN PRAWN MEE ( OFFICE TESTING OUTLET )",
]

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=700)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("Logging in...")
    page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
    page.wait_for_timeout(3000)

    if "/login" in page.url:
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_timeout(2000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_timeout(5000)
        page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
        page.wait_for_timeout(3000)

    log("✓ Logged in\n")
    log("="*70)
    log(f"CLICKING {len(stores_to_click)} STORES")
    log("="*70)

    clicked_count = 0
    for idx, store in enumerate(stores_to_click, 1):
        log(f"\n[{idx}/{len(stores_to_click)}] {store}")
        try:
            btn = page.get_by_text(store, exact=True).first
            btn.scroll_into_view_if_needed(timeout=2000)
            time.sleep(0.5)
            btn.click(timeout=2000)
            time.sleep(1)
            log(f"  ✓ CLICKED")
            clicked_count += 1
        except Exception as e:
            log(f"  ✗ Not visible")

    log("\n" + "="*70)
    log(f"DONE! Clicked {clicked_count}/{len(stores_to_click)} stores")
    log("="*70)
    browser.close()
