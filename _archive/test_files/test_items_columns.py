#!/usr/bin/env python3
"""Quick test to see all table columns"""
import os
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False)
    page = browser.new_page()

    # Login
    page.goto("https://bo.sea.restosuite.ai/login")
    page.fill("#username", EMAIL)
    page.click('button[type="submit"]')
    page.wait_for_selector("#password")
    page.fill("#password", PASSWORD)
    page.click('button[type="submit"]')
    page.wait_for_timeout(3000)

    # Go to item mapping
    page.goto("https://bo.sea.restosuite.ai/takeaway-product-mapping")
    page.wait_for_timeout(5000)

    # Get first row
    row = page.query_selector("table tbody tr:not(.ant-table-measure-row)")
    if row:
        cells = row.query_selector_all("td")
        print(f"\nTotal columns: {len(cells)}\n")
        for i, cell in enumerate(cells):
            text = cell.inner_text().strip()[:50]
            print(f"Column {i}: {text}")

    input("\nPress Enter to close...")
    browser.close()
