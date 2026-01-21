"""
Playwright-based scraper for RestoSuite items
Intercepts API calls to get actual data + scrolls table for all content
"""
import asyncio
import json
from playwright.async_api import async_playwright
import time

class PlaywrightItemsScraper:
    def __init__(self):
        self.all_items = []
        self.api_responses = []

    async def intercept_response(self, response):
        """Intercept API responses to capture item data"""
        try:
            # Look for the API endpoint that returns item data
            if 'itemBindingList' in response.url or 'query' in response.url:
                try:
                    data = await response.json()
                    self.api_responses.append({
                        'url': response.url,
                        'data': data
                    })
                    print(f"  [API] Captured response from: {response.url[:80]}")
                except:
                    pass
        except Exception as e:
            pass

    async def scroll_table_fully(self, page):
        """Scroll table left-to-right, then top-to-bottom to load all content"""
        try:
            await page.evaluate("""
                async () => {
                    const tableBody = document.querySelector('.ant-table-body');
                    if (tableBody) {
                        // Scroll vertically first (top to bottom)
                        const scrollHeight = tableBody.scrollHeight;
                        const scrollStep = 300;

                        for (let pos = 0; pos < scrollHeight; pos += scrollStep) {
                            tableBody.scrollTop = pos;
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                        tableBody.scrollTop = scrollHeight;
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Then scroll horizontally (left to right)
                        const scrollWidth = tableBody.scrollWidth;

                        for (let pos = 0; pos < scrollWidth; pos += scrollStep) {
                            tableBody.scrollLeft = pos;
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                        tableBody.scrollLeft = scrollWidth;
                        await new Promise(resolve => setTimeout(resolve, 500));

                        // Back to top-left
                        tableBody.scrollTop = 0;
                        tableBody.scrollLeft = 0;
                    }
                }
            """)
            print("  • Scrolled table (top-bottom, left-right)")
            await asyncio.sleep(1)
        except Exception as e:
            print(f"  [WARN] Could not scroll: {e}")

    async def set_page_size_100(self, page):
        """Set page size to 100 items"""
        try:
            # Click pagination dropdown
            await page.click('.ant-pagination-options .ant-select-selector', timeout=5000)
            await asyncio.sleep(1)

            # Select 100/page option
            await page.click('text="100 / page"', timeout=5000)
            await asyncio.sleep(2)
            print("  • Set page size to 100")
            return True
        except Exception as e:
            print(f"  [WARN] Could not set page size: {e}")
            return False

    async def extract_items_from_dom(self, page, store_name, platform):
        """Extract items directly from DOM as fallback"""
        items = []
        try:
            # Get all table rows
            rows = await page.query_selector_all('tbody.ant-table-tbody tr')
            print(f"  • Found {len(rows)} rows in DOM")

            for idx, row in enumerate(rows, 1):
                try:
                    # Extract all cell data using JavaScript
                    item_data = await row.evaluate("""
                        (row) => {
                            const cells = row.querySelectorAll('td');
                            const data = {
                                checkbox: cells[0]?.innerText || '',
                                row_num: cells[1]?.innerText || '',
                                image_url: cells[2]?.querySelector('img')?.src || null,
                                item_name: cells[3]?.innerText || '',
                                size_name: cells[4]?.innerText || '',
                                item_type: cells[5]?.innerText || '',
                                menu_group: cells[6]?.innerText || '',
                                sku_id: cells[7]?.innerText || '',
                                price: cells[8]?.innerText || '',
                                sync_status: cells[9]?.innerText || '',
                                third_party_image: cells[10]?.querySelector('img')?.src || null,
                                third_party_name: cells[11]?.innerText || '',
                                third_party_size: cells[12]?.innerText || '',
                                third_party_type: cells[13]?.innerText || '',
                                third_party_menu: cells[14]?.innerText || '',
                                third_party_sku: cells[15]?.innerText || '',
                                third_party_price: cells[16]?.innerText || '',
                                listing_status: cells[17]?.innerText || ''
                            };
                            return data;
                        }
                    """)

                    item_data['store_name'] = store_name
                    item_data['platform'] = platform
                    items.append(item_data)

                except Exception as e:
                    print(f"  [WARN] Error extracting row {idx}: {e}")
                    continue

            return items
        except Exception as e:
            print(f"  [ERROR] DOM extraction failed: {e}")
            return []

    async def scrape_store_platform(self, page, store_name, platform):
        """Scrape items for a specific store and platform"""
        print(f"\n  • Scraping {platform}...")

        try:
            # Switch to platform tab
            await page.click(f'div[role="tab"]:has-text("{platform}")', timeout=5000)
            await asyncio.sleep(3)

            # Check if platform is bound
            try:
                go_bind = await page.query_selector('button:has-text("Go Bind")')
                if go_bind:
                    print(f"  [SKIP] {platform} not bound")
                    return []
            except:
                pass

            # Clear previous API responses
            self.api_responses = []

            # Set page size to 100
            await self.set_page_size_100(page)

            # Scroll table to load all content
            await self.scroll_table_fully(page)

            # Wait a bit for API responses
            await asyncio.sleep(2)

            # Extract from API responses first
            items = []
            if self.api_responses:
                print(f"  [API] Processing {len(self.api_responses)} API responses")
                for resp in self.api_responses:
                    if 'data' in resp and resp['data']:
                        # Try to extract items from the response
                        data = resp['data']
                        if isinstance(data, dict):
                            # Look for item lists in common locations
                            if 'data' in data and isinstance(data['data'], list):
                                items.extend(data['data'])
                            elif 'items' in data and isinstance(data['items'], list):
                                items.extend(data['items'])
                            elif 'list' in data and isinstance(data['list'], list):
                                items.extend(data['list'])

            # If no API data, extract from DOM
            if not items:
                print("  [INFO] No API data, extracting from DOM...")
                items = await self.extract_items_from_dom(page, store_name, platform)
            else:
                print(f"  [OK] Got {len(items)} items from API")
                # Add metadata
                for item in items:
                    item['store_name'] = store_name
                    item['platform'] = platform

            return items

        except Exception as e:
            print(f"  [ERROR] Failed to scrape {platform}: {e}")
            return []

    async def run(self):
        """Main scraping process"""
        async with async_playwright() as p:
            print("\n" + "="*70)
            print("PLAYWRIGHT ITEMS SCRAPER")
            print("="*70)

            # Launch browser
            browser = await p.chromium.launch(headless=False)
            context = await browser.new_context(viewport={'width': 1920, 'height': 1080})
            page = await context.new_page()

            # Set up response interception
            page.on('response', lambda response: asyncio.create_task(self.intercept_response(response)))

            # Login
            print("\nStep 1: Logging in...")
            await page.goto('https://bo.sea.restosuite.ai/takeaway-product-mapping', wait_until='networkidle')
            await asyncio.sleep(2)

            # Wait for email field and fill
            await page.wait_for_selector('#email', timeout=60000)
            await page.fill('#email', 'okchickenrice2018@gmail.com')
            await page.click('button:has-text("Next")')
            await asyncio.sleep(3)

            # Wait for password field and fill
            await page.wait_for_selector('#password', timeout=60000)
            await page.fill('#password', 'Abcd1234!')
            await page.click('button[type="submit"]')
            await asyncio.sleep(7)
            print("[OK] Logged in")

            # Navigate to page
            print("\nStep 2: Navigating to Takeaway Product Mapping...")
            await page.goto('https://bo.sea.restosuite.ai/takeaway-product-mapping', wait_until='networkidle')
            await asyncio.sleep(5)
            print("[OK] Page loaded")

            # Get all parent groups
            print("\nStep 3: Getting parent groups...")
            groups = await page.query_selector_all('span.ant-tree-title')
            group_names = []
            for group in groups[:6]:  # First 6 are parent groups
                name = await group.inner_text()
                group_names.append(name)

            print(f"[OK] Found {len(group_names)} parent groups: {group_names}")

            # Scrape each group
            all_items = []
            for idx, group_name in enumerate(group_names, 1):
                print(f"\n{'='*70}")
                print(f"[GROUP {idx}/{len(group_names)}] {group_name}")
                print("="*70)

                try:
                    # Click to expand/select group
                    await page.click(f'span.ant-tree-title:has-text("{group_name}")', timeout=10000)
                    await asyncio.sleep(2)

                    # Get child stores
                    # Re-query to avoid stale elements
                    tree_nodes = await page.query_selector_all('.ant-tree-treenode')

                    # Find stores under this group
                    store_elements = await page.query_selector_all('span.ant-tree-title')

                    # Click first store (usually the one right after the group name)
                    if len(store_elements) > idx:
                        store_name = await store_elements[idx].inner_text()
                        if store_name != group_name:  # Make sure it's not the group itself
                            print(f"\n  [STORE] {store_name}")
                            await store_elements[idx].click()
                            await asyncio.sleep(3)

                            # Scrape all platforms
                            for platform in ['Grab', 'deliveroo', 'foodPanda']:
                                items = await self.scrape_store_platform(page, store_name, platform)
                                all_items.extend(items)
                                print(f"  [OK] {len(items)} items from {platform}")

                    # Save progress
                    if all_items:
                        filename = f"scraped_items_playwright_group_{idx}_{group_name.replace(' ', '_')}.json"
                        with open(filename, 'w', encoding='utf-8') as f:
                            json.dump(all_items, f, indent=2, ensure_ascii=False)
                        print(f"\n[PROGRESS] Saved {len(all_items)} total items to {filename}")

                except Exception as e:
                    print(f"[ERROR] Failed to process group {group_name}: {e}")
                    continue

            # Final save
            print(f"\n{'='*70}")
            print(f"[COMPLETE] Total items scraped: {len(all_items)}")
            print("="*70)

            with open('scraped_items_playwright_final.json', 'w', encoding='utf-8') as f:
                json.dump(all_items, f, indent=2, ensure_ascii=False)
            print("[OK] Saved to scraped_items_playwright_final.json")

            # Keep browser open for inspection
            input("\nPress Enter to close browser...")
            await browser.close()

if __name__ == '__main__':
    scraper = PlaywrightItemsScraper()
    asyncio.run(scraper.run())
