"""
Simple Playwright scraper that intercepts API responses
to get complete item data with images
"""
import asyncio
import json
from playwright.async_api import async_playwright

captured_data = []

async def intercept_response(response):
    """Capture API responses containing item data"""
    try:
        url = response.url
        # Look for the GraphQL query or REST API endpoint
        if 'itemBindingList' in url or 'graphql' in url or 'query' in url:
            try:
                data = await response.json()
                if data:
                    print(f"[API CAPTURED] {url[:60]}...")
                    captured_data.append({
                        'url': url,
                        'data': data,
                        'timestamp': asyncio.get_event_loop().time()
                    })
            except:
                pass
    except:
        pass

async def main():
    global captured_data

    print("="*70)
    print("API INTERCEPT SCRAPER")
    print("="*70)

    async with async_playwright() as p:
        # Launch browser (visible so you can see what's happening)
        browser = await p.chromium.launch(headless=False, args=['--start-maximized'])
        context = await browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = await context.new_page()

        # Set up API interception
        page.on('response', lambda r: asyncio.create_task(intercept_response(r)))

        print("\n[1] Navigating to login page...")
        await page.goto('https://bo.sea.restosuite.ai/login')

        print("[2] Waiting for page to load...")
        await page.wait_for_load_state('networkidle')
        await asyncio.sleep(2)

        print("[3] Filling email...")
        await page.wait_for_selector('#email', state='visible', timeout=30000)
        await page.fill('#email', 'okchickenrice2018@gmail.com')
        await page.click('button:has-text("Next")')
        await asyncio.sleep(2)

        print("[4] Filling password...")
        await page.wait_for_selector('#password', state='visible', timeout=30000)
        await page.fill('#password', 'Abcd1234!')
        await page.click('button[type="submit"]')

        print("[5] Waiting for login to complete...")
        await asyncio.sleep(7)

        print("[6] Navigating to Takeaway Product Mapping...")
        await page.goto('https://bo.sea.restosuite.ai/takeaway-product-mapping')
        await page.wait_for_load_state('networkidle')
        await asyncio.sleep(3)

        print("[7] Page loaded. Waiting for store list...")
        await asyncio.sleep(3)

        # Click first store to trigger API call
        print("[8] Clicking first store...")
        await page.click('span.ant-tree-title >> nth=1')
        await asyncio.sleep(5)

        print(f"\n[9] Captured {len(captured_data)} API responses so far")

        # Try to set page size to 100
        try:
            print("[10] Setting page size to 100...")
            await page.click('.ant-pagination-options .ant-select-selector', timeout=5000)
            await asyncio.sleep(1)
            await page.click('text="100 / page"')
            await asyncio.sleep(3)
            print("     Page size set to 100")
        except Exception as e:
            print(f"     Could not set page size: {e}")

        # Scroll the table
        print("[11] Scrolling table...")
        await page.evaluate("""
            async () => {
                const tableBody = document.querySelector('.ant-table-body');
                if (tableBody) {
                    // Scroll down
                    for (let i = 0; i < 5; i++) {
                        tableBody.scrollTop = tableBody.scrollHeight;
                        await new Promise(r => setTimeout(r, 500));
                    }
                    // Scroll right
                    for (let i = 0; i < 5; i++) {
                        tableBody.scrollLeft = tableBody.scrollWidth;
                        await new Promise(r => setTimeout(r, 500));
                    }
                }
            }
        """)
        await asyncio.sleep(2)

        # Switch tabs to trigger more API calls
        for tab_name in ['deliveroo', 'foodPanda']:
            print(f"[12] Switching to {tab_name} tab...")
            try:
                await page.click(f'div[role="tab"]:has-text("{tab_name}")')
                await asyncio.sleep(4)

                # Scroll again
                await page.evaluate("""
                    async () => {
                        const tableBody = document.querySelector('.ant-table-body');
                        if (tableBody) {
                            tableBody.scrollTop = tableBody.scrollHeight;
                            tableBody.scrollLeft = tableBody.scrollWidth;
                        }
                    }
                """)
                await asyncio.sleep(2)
            except:
                print(f"     Could not switch to {tab_name}")

        print(f"\n[DONE] Captured {len(captured_data)} total API responses")

        # Save all captured data
        with open('api_responses_raw.json', 'w', encoding='utf-8') as f:
            json.dump(captured_data, f, indent=2, ensure_ascii=False)
        print(f"[SAVED] Raw API responses to api_responses_raw.json")

        # Try to extract items from the captured data
        all_items = []
        for resp in captured_data:
            data = resp.get('data', {})
            if isinstance(data, dict):
                # Try different possible locations for item arrays
                for key in ['data', 'items', 'list', 'itemBindingList']:
                    if key in data and isinstance(data[key], list):
                        all_items.extend(data[key])
                        print(f"     Found {len(data[key])} items in response.{key}")

        if all_items:
            with open('extracted_items.json', 'w', encoding='utf-8') as f:
                json.dump(all_items, f, indent=2, ensure_ascii=False)
            print(f"\n[SUCCESS] Extracted {len(all_items)} items to extracted_items.json")
        else:
            print("\n[INFO] No items extracted from API responses")
            print("       Check api_responses_raw.json manually to see the structure")

        input("\nPress Enter to close browser...")
        await browser.close()

if __name__ == '__main__':
    asyncio.run(main())
