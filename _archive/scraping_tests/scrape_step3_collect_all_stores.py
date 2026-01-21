#!/usr/bin/env python3
"""
Step 3: Expand all groups and collect all store names
"""

import sys
import json
from playwright.sync_api import sync_playwright

EMAIL = "okchickenrice2018@gmail.com"
PASSWORD = "90267051@Arc"
BASE_URL = "https://bo.sea.restosuite.ai"

def log(msg):
    print(msg, file=sys.stderr, flush=True)

def main():
    log("=" * 70)
    log("STEP 3: COLLECT ALL STORES FROM ALL GROUPS")
    log("=" * 70)

    stores_by_group = {}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})

        try:
            # Login
            log("\n[1] Logging in...")
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
            page.wait_for_timeout(3000)
            log("[OK] Logged in")

            # Navigate to page
            log("\n[2] Going to takeaway-product-mapping page...")
            page.goto(f"{BASE_URL}/takeaway-product-mapping", wait_until="networkidle")
            page.wait_for_timeout(5000)
            log("[OK] Page loaded")

            # Make sure we're on GROUP tab
            log("\n[3] Ensuring we're on GROUP tab...")
            try:
                page.click("text=ACHIEVERS RESOURCE CONSULTANCY PTE LTD", timeout=10000)
                page.wait_for_timeout(2000)
                page.click("text=Group", timeout=5000)
                page.wait_for_timeout(2000)
                page.keyboard.press("Escape")
                page.wait_for_timeout(1000)
                log("[OK] On GROUP tab")
            except Exception as e:
                log(f"  [WARNING] Could not switch to Group tab: {e}")

            # Get all tree switchers (parent groups)
            log("\n[4] Finding all parent groups...")
            page.wait_for_selector(".ant-tree", timeout=10000)
            page.wait_for_timeout(2000)

            # Get all switchers
            all_switchers = page.query_selector_all(".ant-tree-switcher")
            log(f"  Found {len(all_switchers)} tree switchers")

            # Expand each group and collect stores
            for idx, switcher in enumerate(all_switchers, 1):
                try:
                    log(f"\n[Group {idx}/{len(all_switchers)}]")

                    # Get the group name (title is in the next sibling)
                    # The structure is: <span class="ant-tree-switcher"></span><span class="ant-tree-title">Group Name</span>
                    parent = switcher.evaluate_handle("el => el.parentElement")
                    title_elem = parent.query_selector("span.ant-tree-title")

                    if title_elem:
                        group_name = title_elem.inner_text().strip()
                        log(f"  Group: {group_name}")

                        # Check if switcher is closed
                        switcher_class = switcher.get_attribute("class")

                        if "ant-tree-switcher_close" in switcher_class:
                            log(f"  Expanding {group_name}...")
                            switcher.click()
                            page.wait_for_timeout(2000)
                            log("  [OK] Expanded")
                        else:
                            log("  Already expanded")

                        # Now collect all child stores under this group
                        # After expanding, the child nodes appear in the tree
                        # We need to find the treenode that contains this switcher
                        # and then find all child nodes

                        page.wait_for_timeout(1000)

                        # Get all tree titles again (now includes children)
                        all_titles = page.query_selector_all("span.ant-tree-title")

                        # The children appear right after the parent in the DOM
                        # We'll collect all titles that appear after this group's title
                        # until we hit another parent group

                        found_group = False
                        stores = []

                        for title in all_titles:
                            title_text = title.inner_text().strip()

                            if title_text == group_name:
                                found_group = True
                                continue

                            if found_group:
                                # Check if this title has a switcher (meaning it's another parent group)
                                parent_node = title.evaluate_handle("el => el.parentElement")
                                has_switcher = parent_node.query_selector(".ant-tree-switcher")

                                if has_switcher:
                                    # This is another parent group, stop collecting
                                    break

                                # This is a child store
                                if len(title_text) > 3:  # Real store names
                                    stores.append(title_text)

                        stores_by_group[group_name] = stores
                        log(f"  Found {len(stores)} stores under {group_name}")
                        for store in stores:
                            log(f"    - {store}")

                except Exception as e:
                    log(f"  [ERROR] {e}")
                    import traceback
                    traceback.print_exc(file=sys.stderr)
                    continue

            log("\n" + "=" * 70)
            log("STEP 3 COMPLETE")
            log("=" * 70)
            log(f"\nTotal groups: {len(stores_by_group)}")
            log(f"Total stores: {sum(len(stores) for stores in stores_by_group.values())}")

            # Save to JSON
            with open('stores_by_group.json', 'w', encoding='utf-8') as f:
                json.dump(stores_by_group, f, indent=2, ensure_ascii=False)
            log("\n[SAVED] stores_by_group.json")

            log("\nBrowser will stay open for 30 seconds...")
            page.wait_for_timeout(30000)

        except Exception as e:
            log(f"\n[ERROR] {e}")
            import traceback
            traceback.print_exc(file=sys.stderr)

        finally:
            log("\nClosing browser...")
            browser.close()

if __name__ == "__main__":
    main()
