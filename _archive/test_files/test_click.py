#!/usr/bin/env python3
"""Quick test to see page state and take screenshot"""

import os
from playwright.sync_api import sync_playwright

EMAIL = os.getenv("RESTOSUITE_EMAIL", "okchickenrice2018@gmail.com")
PASSWORD = os.getenv("RESTOSUITE_PASSWORD", "90267051@Arc")
BASE_URL = "https://bo.sea.restosuite.ai"

def login(page):
    page.goto(f"{BASE_URL}/login")
    page.wait_for_selector("#username", timeout=10000)
    page.fill("#username", EMAIL)
    page.click('button[type="submit"]')
    page.wait_for_selector("#password", timeout=10000)
    page.fill("#password", PASSWORD)
    page.click('button[type="submit"]')
    page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
    return True

def select_organization(page):
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

        page.screenshot(path="test_page_initial.png")
        print("Screenshot saved: test_page_initial.png")

        # Check what divs with cursor-pointer rounded-md exist
        divs_info = page.evaluate("""
            () => {
                const divs = Array.from(document.querySelectorAll('div.cursor-pointer.rounded-md'));
                return divs.map(div => {
                    const rect = div.getBoundingClientRect();
                    const span = div.querySelector('span[class*="truncate"]');
                    return {
                        text: div.textContent?.substring(0, 60),
                        hasSpan: !!span,
                        spanText: span?.textContent,
                        top: rect.top,
                        left: rect.left
                    };
                });
            }
        """)

        print("\n=== Divs with cursor-pointer rounded-md ===")
        for i, div in enumerate(divs_info):
            print(f"{i+1}. Top: {div['top']:.1f}, Left: {div['left']:.1f}")
            print(f"   Has span: {div['hasSpan']}, Span text: {div['spanText']}")
            print(f"   Full text: {div['text']}")
            print()

    browser.close()
