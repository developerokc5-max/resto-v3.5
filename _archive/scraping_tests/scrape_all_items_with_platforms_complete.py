#!/usr/bin/env python3
"""
Complete Platform Scraper - Properly expands BRANDS to find all OUTLETS
"""

import sys
import json
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr, flush=True)

def expand_all_brands_completely(page):
    """Expand all brands and their sub-groups to reveal ALL outlets"""
    try:
        log("\n[EXPAND] Expanding all brands and sub-groups...")

        # Keep expanding until no more closed groups exist
        max_rounds = 20
        total_expanded = 0

        for round_num in range(max_rounds):
            # Find ALL closed switchers (brands and sub-groups)
            closed = page.query_selector_all(".ant-tree-switcher_close")

            if not closed:
                log(f"[OK] Fully expanded (total: {total_expanded} groups)")
                break

            log(f"  Round {round_num + 1}: Expanding {len(closed)} groups...")

            # Expand each one
            for switcher in closed:
                try:
                    switcher.click()
                    page.wait_for_timeout(150)
                    total_expanded += 1
                except:
                    pass

            page.wait_for_timeout(1000)

        if total_expanded == 0:
            log("[WARNING] No groups were expanded - tree may already be open")

        return True

    except Exception as e:
        log(f"[ERROR] {e}")
        return False

def set_pagination_to_100(page):
    """Set pagination to 100/page"""
    try:
        log("\n[PAGINATION] Setting to 100/page...")

        # Click first outlet to show table
        titles = page.query_selector_all("span.ant-tree-title")
        for title in titles:
            text = title.inner_text().strip()
            if "@" in text or "OFFICE" in text:
                log(f"  Clicking: {text}")
                title.click()
                page.wait_for_timeout(3000)
                break

        # Wait for pagination dropdown
        page.wait_for_selector(".ant-pagination-options", timeout=10000)

        # Check if already 100
        current = page.query_selector(".ant-select-selection-item")
        if current and "100" in current.inner_text():
            log("[OK] Already 100/page")
            return True

        # Open dropdown
        page.click(".ant-pagination-options .ant-select-selector")
        page.wait_for_timeout(1000)

        # Find and click 100 / page option
        options = page.query_selector_all(".ant-select-item-option")
        for opt in options:
            if "100" in opt.inner_text():
                opt.click()
                page.wait_for_timeout(2000)
                log("[OK] Set to 100/page")
                return True

        log("[WARNING] Could not find 100/page option")
        return False

    except Exception as e:
        log(f"[ERROR] {e}")
        return False

def get_all_outlets_from_tree(page):
    """Get ALL outlets (leaf nodes) from fully expanded tree"""
    try:
        log("\n[SCAN] Finding all outlets...")

        outlets = []
        nodes = page.query_selector_all(".ant-tree-treenode")

        for node in nodes:
            node_class = node.get_attribute("class")

            # Leaf nodes = actual outlets (includes BOTH leaf and leaf-last)
            # FIX: Look for ANY leaf node, not just the last one
            if ("ant-tree-treenode-leaf-last" in node_class or
                "ant-tree-treenode-switcher-leaf-last" in node_class):
                title = node.query_selector("span.ant-tree-title")
                if title:
                    name = title.inner_text().strip()
                    if len(name) > 3:
                        outlets.append(name)
            # Also check for regular leaf nodes (not the last child)
            elif "ant-tree-treenode" in node_class and "leaf" in node_class:
                # Make sure it's not a parent node with children
                if not node.query_selector(".ant-tree-switcher"):
                    title = node.query_selector("span.ant-tree-title")
                    if title:
                        name = title.inner_text().strip()
                        if len(name) > 3 and name not in outlets:
                            outlets.append(name)

        log(f"[OK] Found {len(outlets)} outlets")
        for i, name in enumerate(outlets, 1):
            log(f"  {i:2d}. {name}")

        return outlets

    except Exception as e:
        log(f"[ERROR] {e}")
        return []

def re_expand_if_collapsed(page):
    """Re-expand any groups that collapsed"""
    try:
        closed = page.query_selector_all(".ant-tree-switcher_close")
        if closed:
            log(f"  [REOPEN] Expanding {len(closed)} collapsed groups")
            for s in closed:
                try:
                    s.click()
                    page.wait_for_timeout(100)
                except:
                    pass
            page.wait_for_timeout(800)
    except:
        pass

def select_outlet(page, outlet_name):
    """Select an outlet"""
    try:
        log(f"\n  [SELECT] {outlet_name}")

        # Re-expand tree if needed
        re_expand_if_collapsed(page)

        # Find and click outlet
        titles = page.query_selector_all("span.ant-tree-title")
        for title in titles:
            if title.inner_text().strip() == outlet_name:
                title.scroll_into_view_if_needed()
                page.wait_for_timeout(300)
                title.click()
                page.wait_for_timeout(4000)
                log(f"  [OK]")
                return True

        log(f"  [SKIP] Not found")
        return False

    except Exception as e:
        log(f"  [ERROR] {e}")
        return False

def check_has_items(page):
    """Check if table has items"""
    try:
        page.wait_for_timeout(1500)
        rows = page.query_selector_all(".ant-table-tbody tr.ant-table-row")
        count = len(rows)

        if count > 0:
            log(f"  [ITEMS] {count} rows")
            return True
        else:
            log(f"  [SKIP] No items")
            return False
    except:
        return False

def switch_to_platform(page, platform):
    """Switch platform tab"""
    try:
        log(f"\n    [{platform}]")
        page.click(f"text={platform}")
        page.wait_for_timeout(3500)
        return True
    except Exception as e:
        log(f"    [ERROR] {e}")
        return False

def scrape_items(page, outlet, platform):
    """Scrape items from table"""
    items = []
    try:
        page.wait_for_selector(".ant-table-tbody", timeout=5000)
        page.wait_for_timeout(1500)

        rows = page.query_selector_all(".ant-table-tbody tr.ant-table-row")

        for row in rows:
            try:
                cells = row.query_selector_all("td")
                if len(cells) >= 3:
                    items.append({
                        "index": cells[0].inner_text().strip(),
                        "item_name": cells[1].inner_text().strip(),
                        "size_name": cells[2].inner_text().strip(),
                        "outlet": outlet,
                        "platform": platform
                    })
            except:
                continue

        log(f"    [OK] {len(items)} items")
        return items

    except Exception as e:
        log(f"    [ERROR] {e}")
        return []

def main():
    log("=" * 80)
    log("COMPLETE SCRAPER - ALL BRANDS, ALL OUTLETS, ALL PLATFORMS")
    log("=" * 80)

    all_data = []

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\n[LOGIN]")
            page.goto(f"{BASE_URL}/login", wait_until="networkidle")
            page.wait_for_timeout(2000)

            if "/login" not in page.url:
                log("[OK] Already logged in")
            else:
                page.wait_for_selector("#username", timeout=30000)
                page.fill("#username", EMAIL)
                page.click('button[type="submit"]')
                page.wait_for_timeout(2000)
                page.wait_for_selector("#password", timeout=30000)
                page.fill("#password", PASSWORD)
                page.click('button[type="submit"]')
                page.wait_for_url(lambda url: "/login" not in url, timeout=15000)
                page.wait_for_timeout(3000)
                log("[OK]")

            # Navigate
            log("\n[NAVIGATE]")
            page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
            page.wait_for_timeout(5000)
            log("[OK]")

            # Group tab
            log("\n[GROUP TAB]")
            try:
                page.click("text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD", timeout=10000)
                page.wait_for_timeout(2000)
                page.click("text=Group", timeout=5000)
                page.wait_for_timeout(2000)
                page.keyboard.press("Escape")
                page.wait_for_timeout(1000)
                log("[OK]")
            except:
                pass

            # Expand all brands
            expand_all_brands_completely(page)

            # Set pagination
            set_pagination_to_100(page)

            # Get outlets
            outlets = get_all_outlets_from_tree(page)

            if not outlets:
                log("[ERROR] No outlets!")
                return

            # Process outlets
            log(f"\n{'='*80}")
            log(f"PROCESSING {len(outlets)} OUTLETS")
            log(f"{'='*80}")

            for idx, outlet_name in enumerate(outlets, 1):
                log(f"\n[{idx}/{len(outlets)}] {outlet_name}")
                log("-" * 80)

                if not select_outlet(page, outlet_name):
                    continue

                if not check_has_items(page):
                    continue

                # Scan platforms
                for platform in ["Grab", "deliveroo", "foodPanda"]:
                    if switch_to_platform(page, platform):
                        items = scrape_items(page, outlet_name, platform)
                        all_data.extend(items)

                log(f"  [COMPLETE]")

            # Results
            log(f"\n{'='*80}")
            log("SCRAPING COMPLETE")
            log(f"{'='*80}")
            log(f"\nTotal items: {len(all_data)}")

            # Save
            output = "scraped_all_items_with_platforms_complete.json"
            with open(output, 'w', encoding='utf-8') as f:
                json.dump(all_data, f, indent=2, ensure_ascii=False)
            log(f"[SAVED] {output}")

            # Stats
            grab = len([x for x in all_data if x['platform'] == 'Grab'])
            deliveroo = len([x for x in all_data if x['platform'] == 'deliveroo'])
            foodpanda = len([x for x in all_data if x['platform'] == 'foodPanda'])

            log(f"\nPlatforms:")
            log(f"  Grab: {grab}")
            log(f"  deliveroo: {deliveroo}")
            log(f"  foodPanda: {foodpanda}")

            outlets_count = {}
            for item in all_data:
                o = item['outlet']
                outlets_count[o] = outlets_count.get(o, 0) + 1

            log(f"\nOutlets scanned: {len(outlets_count)}")
            for name, count in sorted(outlets_count.items()):
                log(f"  {name}: {count}")

            log("\nPress Ctrl+C to close...")
            page.wait_for_timeout(90000)

        except Exception as e:
            log(f"\n[ERROR] {e}")
            import traceback
            traceback.print_exc(file=sys.stderr)

        finally:
            try:
                browser.close()
            except:
                pass

if __name__ == "__main__":
    main()
