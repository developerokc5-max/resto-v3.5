#!/usr/bin/env python3
"""Debug script to capture page HTML and find the correct organization selector"""

import os
from playwright.sync_api import sync_playwright

EMAIL = os.getenv("RESTOSUITE_EMAIL", "okchickenrice2018@gmail.com")
PASSWORD = os.getenv("RESTOSUITE_PASSWORD", "90267051@Arc")
BASE_URL = "https://bo.sea.restosuite.ai"

def login(page):
    """Login to RestoSuite"""
    try:
        page.goto(f"{BASE_URL}/login")
        page.wait_for_selector("#username", timeout=10000)
        page.fill("#username", EMAIL)
        page.click('button[type="submit"]')
        page.wait_for_selector("#password", timeout=10000)
        page.fill("#password", PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
        return True
    except:
        return False

def select_organization(page):
    """Select organization"""
    try:
        page.wait_for_timeout(2000)
        page.click("text=ACHIEVERS RESOURCE CONS", timeout=5000)
        page.wait_for_timeout(1000)
        page.click("text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD", timeout=5000)
        page.wait_for_timeout(2000)
        return True
    except:
        return True

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page()

    if login(page) and select_organization(page):
        page.goto(f"{BASE_URL}/takeaway-product-mapping")
        page.wait_for_timeout(5000)

        # Save the full page HTML
        html = page.content()
        with open("debug_page_full.html", "w", encoding="utf-8") as f:
            f.write(html)

        # Get all header elements structure
        header_info = page.evaluate("""
            () => {
                // Find the header/nav area
                const header = document.querySelector('header') ||
                               document.querySelector('nav') ||
                               document.querySelector('[class*="header"]') ||
                               document.querySelector('[class*="Header"]');

                if (!header) {
                    return {error: 'No header found'};
                }

                // Get all clickable elements in header
                const clickables = Array.from(header.querySelectorAll('*')).filter(el => {
                    const styles = window.getComputedStyle(el);
                    return styles.cursor === 'pointer' || el.onclick || el.className.includes('click');
                });

                return clickables.map(el => ({
                    tag: el.tagName,
                    classes: el.className,
                    text: el.textContent?.substring(0, 60),
                    position: el.getBoundingClientRect(),
                    hasDownArrow: !!el.querySelector('svg[data-icon="down"]') ||
                                   !!el.querySelector('svg[class*="down"]') ||
                                   el.innerHTML.includes('chevron-down')
                }));
            }
        """)

        print("=== HEADER CLICKABLE ELEMENTS ===")
        import json
        print(json.dumps(header_info, indent=2))

        page.screenshot(path="debug_page_screenshot.png")

    browser.close()
