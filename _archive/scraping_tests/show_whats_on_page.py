import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False)
    page = p.chromium.launch(headless=False).new_page()
    page.set_viewport_size({"width": 1920, "height": 1080})

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

    # Take screenshot
    page.screenshot(path="page_state.png", full_page=True)
    print("Screenshot saved: page_state.png", file=sys.stderr)

    # Find ALL text elements and print them
    all_elements = page.query_selector_all("*")
    store_likes = []

    for elem in all_elements:
        try:
            text = elem.inner_text().strip()
            if "@" in text and 5 < len(text) < 50:
                store_likes.append(text)
        except:
            pass

    # Deduplicate
    unique_stores = list(set(store_likes))
    print(f"\nFound {len(unique_stores)} unique elements with @ symbol:", file=sys.stderr)
    for store in sorted(unique_stores):
        print(f"  - {store}", file=sys.stderr)

    page.wait_for_timeout(60000)  # Wait so you can see the browser
    browser.close()
