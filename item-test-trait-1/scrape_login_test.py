#!/usr/bin/env python3
"""
Test scraper - Login to RestoSuite
All logs will be saved in this folder
"""

import sys
from playwright.sync_api import sync_playwright
from datetime import datetime

# Credentials
EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

# Log file path
LOG_FILE = "item-test-trait-1/scrape_login.log"

def log(msg):
    """Log to both console and file"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_msg = f"[{timestamp}] {msg}"
    print(log_msg, file=sys.stderr)
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(log_msg + "\n")

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=500)
    page = browser.new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

    log("="*70)
    log("STARTING LOGIN TEST")
    log("="*70)

    # Navigate to the page
    log(f"Navigating to {BASE_URL}/takeaway-product-mapping")
    page.goto(f"{BASE_URL}/takeaway-product-mapping")
    page.wait_for_timeout(5000)

    # Check if we need to login
    if "/login" in page.url:
        log("Login page detected, entering credentials...")

        # Enter email
        log("Entering email...")
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_timeout(2000)

        # Enter password
        log("Entering password...")
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_timeout(5000)

        # Navigate to target page after login
        log("Redirecting to product mapping page...")
        page.goto(f"{BASE_URL}/takeaway-product-mapping")
        page.wait_for_timeout(5000)

    # Verify we're logged in
    if "/login" in page.url:
        log("ERROR: Still on login page - login failed")
    else:
        log("✓ LOGIN SUCCESSFUL!")
        log(f"Current URL: {page.url}")

    # Take screenshot
    screenshot_path = "item-test-trait-1/login_success.png"
    page.screenshot(path=screenshot_path)
    log(f"Screenshot saved: {screenshot_path}")

    log("="*70)
    log("TEST COMPLETE")
    log("="*70)

    # Keep browser open for 10 seconds so you can see
    page.wait_for_timeout(10000)

    browser.close()
    log("Browser closed")
