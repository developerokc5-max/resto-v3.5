#!/usr/bin/env python3
"""
Working Playwright scraper that uses the LEFT TREE VIEW to select stores
NOT the dropdown - uses the ant-tree on the left side of the page
"""

import json
import sys
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr, flush=True)

def main():
    log("="*70)
    log("WORKING PLAYWRIGHT SCRAPER - TREE VIEW")
    log("="*70)

    result = {"success": False, "stores": {}, "total_items": 0}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\nStep 1: Logging in...")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(2000)
            page.wait_for_selector("#username", timeout=30000)
            page.fill("#username", EMAIL)
            page.click('button[type="submit"]')
            page.wait_for_timeout(2000)
            page.wait_for_selector("#password", timeout=30000)
            page.fill("#password", PASSWORD)
            page.click('button[type="submit"]')
            page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
            page.wait_for_timeout(5000)
            log("✓ Login successful")

            # Navigate to item mapping
            log("\nStep 2: Going to Item Mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping")
            page.wait_for_timeout(7000)
            log("✓ On Item Mapping page")

            # Get stores from TREE VIEW on the left
            log("\nStep 3: Getting stores from tree view...")
            page.wait_for_selector(".ant-tree", timeout=10000)
            page.wait_for_timeout(2000)

            # Get all tree nodes (these are the parent groups and child stores)
            tree_titles = page.query_selector_all("span.ant-tree-title")

            stores_to_scrape = []
            for title_elem in tree_titles:
                title_text = title_elem.inner_text().strip()
                # Skip if it's too short or contains "testing"
                if len(title_text) > 5 and "testing" not in title_text.lower():
                    stores_to_scrape.append(title_text)

            log(f"✓ Found {len(stores_to_scrape)} items in tree")
            for idx, name in enumerate(stores_to_scrape[:10], 1):  # Show first 10
                log(f"    {idx}. {name}")

            # Scrape each store
            scraped_count = 0
            for idx, store_name in enumerate(stores_to_scrape, 1):
                log(f"\n[{idx}/{len(stores_to_scrape)}] {store_name}")
                log("="*70)

                try:
                    # Click on the store in the tree
                    log("  Clicking store in tree...")
                    page.wait_for_timeout(1000)

                    # Find and click the exact tree title
                    store_elem = page.locator(f"span.ant-tree-title:has-text('{store_name}')").first
                    store_elem.click(timeout=5000)
                    page.wait_for_timeout(4000)

                    # Check if table loaded
                    try:
                        page.wait_for_selector("table tbody tr:not(.ant-table-measure-row)", timeout=5000)
                    except:
                        log("  ⚠ No table found, skipping (might be a parent group)")
                        continue

                    log("  ✓ Store selected, table loaded")

                    # Set page size to 100
                    try:
                        log("  Setting page size to 100...")
                        page.click(".ant-pagination-options .ant-select-selector", timeout=3000)
                        page.wait_for_timeout(1000)
                        page.click("text=/100.*page/i", timeout=3000)
                        page.wait_for_timeout(3000)
                        log("  ✓ Page size set")
                    except:
                        log("  (page size selector not found or already 100)")

                    # Scroll table to load all content
                    log("  Scrolling table...")
                    page.evaluate("""
                        const tableBody = document.querySelector('.ant-table-body');
                        if (tableBody) {
                            // Scroll vertically
                            tableBody.scrollTop = tableBody.scrollHeight;
                            // Scroll horizontally
                            tableBody.scrollLeft = tableBody.scrollWidth;
                        }
                    """)
                    page.wait_for_timeout(2000)

                    # Get all rows
                    rows = page.query_selector_all("table tbody tr:not(.ant-table-measure-row):not(.ant-table-placeholder)")
                    log(f"  Found {len(rows)} rows in table")

                    items = []
                    for row in rows:
                        try:
                            cells = row.query_selector_all("td")
                            if len(cells) < 10:
                                continue

                            # Extract data - adjust column indices as needed
                            img = cells[2].query_selector("img") if len(cells) > 2 else None
                            image_url = img.get_attribute("src") if img else None

                            name = cells[3].inner_text().strip() if len(cells) > 3 else ""
                            category = cells[6].inner_text().strip() if len(cells) > 6 else ""
                            sku = cells[7].inner_text().strip() if len(cells) > 7 else ""

                            price_text = cells[8].inner_text().strip() if len(cells) > 8 else "0"
                            try:
                                price = float(price_text.replace("S$", "").replace("$", "").strip())
                            except:
                                price = 0.0

                            status_text = cells[17].inner_text().strip().lower() if len(cells) > 17 else ""
                            is_listed = "listed" in status_text

                            if name:  # Only add if we have a name
                                items.append({
                                    "name": name,
                                    "image_url": image_url,
                                    "category": category,
                                    "sku": sku,
                                    "price": price,
                                    "is_available": is_listed,
                                })
                        except Exception as e:
                            log(f"  ⚠ Error parsing row: {e}")
                            continue

                    if items:
                        result["stores"][store_name] = items
                        scraped_count += 1
                        log(f"  ✓ Scraped {len(items)} items")

                        # Save progress every 5 stores
                        if scraped_count % 5 == 0:
                            with open(f'scrape_progress_{scraped_count}.json', 'w', encoding='utf-8') as f:
                                json.dump(result, f, indent=2, ensure_ascii=False)
                            log(f"  [PROGRESS SAVED] {scraped_count} stores so far")
                    else:
                        log("  ⚠ No items extracted from table")

                except Exception as e:
                    log(f"  ✗ Error: {e}")
                    import traceback
                    traceback.print_exc(file=sys.stderr)
                    continue

            result["total_items"] = sum(len(items) for items in result["stores"].values())
            result["success"] = True
            result["message"] = f"Scraped {result['total_items']} items from {len(result['stores'])} stores"

            log("\n" + "="*70)
            log("COMPLETE!")
            log(f"Stores: {len(result['stores'])}")
            log(f"Items: {result['total_items']}")
            log("="*70)

        except Exception as e:
            log(f"\n✗ Fatal error: {e}")
            result["message"] = str(e)
            import traceback
            traceback.print_exc(file=sys.stderr)

        finally:
            log("\nClosing browser...")
            browser.close()

    # Print result as JSON to stdout
    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()
