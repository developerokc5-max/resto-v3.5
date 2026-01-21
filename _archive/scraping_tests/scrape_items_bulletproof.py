#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Bulletproof Items Scraper for RestoSuite
========================================
This script ensures proper login, group selection, and navigation
before scraping items from the takeaway product mapping page.
"""

import os
import sys
import time
import json
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import mysql.connector
from dotenv import load_dotenv

# Fix Windows console encoding
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding='utf-8')

# Load environment variables
load_dotenv()

# Database configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_DATABASE', 'resto_db')
}

# RestoSuite credentials
RESTOSUITE_EMAIL = os.getenv('RESTOSUITE_EMAIL', 'okchickenrice2018@gmail.com')
RESTOSUITE_PASSWORD = os.getenv('RESTOSUITE_PASSWORD', '90267051@Arc')
TARGET_GROUP = "ACHIEVERS RESOURCE CONSULTANCY PTE LTD"

class BulletproofItemsScraper:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.db = None
        self.cursor = None

    def setup_driver(self):
        """Initialize Chrome driver with options"""
        print("Setting up Chrome driver...")
        chrome_options = Options()
        chrome_options.add_argument('--start-maximized')
        chrome_options.add_argument('--disable-blink-features=AutomationControlled')
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)

        self.driver = webdriver.Chrome(options=chrome_options)
        self.wait = WebDriverWait(self.driver, 20)
        print("[OK] Chrome driver ready")

    def connect_database(self):
        """Connect to MySQL database"""
        try:
            self.db = mysql.connector.connect(**DB_CONFIG)
            self.cursor = self.db.cursor(dictionary=True)
            print("[OK] Database connected")
            return True
        except Exception as e:
            print(f"[SKIP] Database connection failed: {e}")
            print("[INFO] Continuing without database...")
            return True  # Continue without database for now

    def login(self):
        """Login to RestoSuite"""
        print("\nStep 1: Logging in to RestoSuite...")

        try:
            # Navigate to login page
            self.driver.get("https://bo.sea.restosuite.ai/login")
            time.sleep(3)

            # Wait for and fill email with longer timeout
            email_input = WebDriverWait(self.driver, 30).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='text'], input[placeholder*='email'], input[placeholder*='Email']"))
            )
            email_input.clear()
            email_input.send_keys(RESTOSUITE_EMAIL)
            print(f"  • Email entered: {RESTOSUITE_EMAIL}")

            # Click Next button (if it exists)
            try:
                next_button = self.driver.find_element(By.XPATH, "//button[contains(., 'Next')] | //button[@type='submit']")
                next_button.click()
                print("  • 'Next' button clicked")
                time.sleep(2)
            except NoSuchElementException:
                print("  • No 'Next' button found, continuing...")

            # Wait for password field and fill it
            try:
                password_input = self.wait.until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='password']"))
                )
                password_input.clear()
                password_input.send_keys(RESTOSUITE_PASSWORD)
                print("  • Password entered")

                # Click login/submit button
                login_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
                login_button.click()
                print("  • Login button clicked")
            except TimeoutException:
                print("[ERROR] Password field not found after clicking Next")
                return False

            # Wait for login to complete
            time.sleep(5)

            # Check if we're logged in
            if "login" not in self.driver.current_url.lower():
                print("[OK] Login successful")
                return True
            else:
                print("[ERROR] Login failed - still on login page")
                return False

        except Exception as e:
            print(f"[ERROR] Login error: {e}")
            return False

    def navigate_to_takeaway_mapping(self):
        """Navigate to Takeaway Product Mapping page"""
        print("\nStep 2: Navigating to Takeaway Product Mapping...")

        try:
            # Direct navigation to the page
            self.driver.get("https://bo.sea.restosuite.ai/takeaway-product-mapping")
            time.sleep(3)

            # Verify we're on the right page
            if "takeaway-product-mapping" in self.driver.current_url:
                print("[OK] Successfully navigated to Takeaway Product Mapping")
                return True
            else:
                print(f"[ERROR] Navigation failed - current URL: {self.driver.current_url}")
                return False

        except Exception as e:
            print(f"[ERROR] Navigation error: {e}")
            return False

    def ensure_correct_group(self):
        """Ensure we're in the correct group (ACHIEVERS RESOURCE CONSULTANCY PTE LTD)"""
        print(f"\nStep 3: Ensuring correct group is selected ({TARGET_GROUP})...")

        try:
            # Wait for page to load
            time.sleep(2)

            # Look for the group dropdown/selector
            # It's usually in the top bar with the organization name
            group_selectors = [
                "//div[contains(@class, 'organization')]//span[contains(text(), 'ACHIEVERS')]",
                "//span[contains(text(), 'ACHIEVERS RESOURCE CONSULTANCY')]",
                "//*[contains(text(), 'ACHIEVERS RESOURCE CONSULTANCY PTE LTD')]"
            ]

            current_group = None
            for selector in group_selectors:
                try:
                    element = self.driver.find_element(By.XPATH, selector)
                    current_group = element.text
                    print(f"  • Current group detected: {current_group}")
                    break
                except NoSuchElementException:
                    continue

            if current_group and TARGET_GROUP in current_group:
                print("[OK] Already in correct group")
                return True

            # If not in correct group, try to switch
            print("  • Attempting to switch to correct group...")

            # Try to find and click the group dropdown
            dropdown_selectors = [
                "//div[contains(@class, 'organization-selector')]",
                "//div[contains(@class, 'org-dropdown')]",
                "//*[@role='button' and contains(., 'ACHIEVERS')]"
            ]

            for selector in dropdown_selectors:
                try:
                    dropdown = self.driver.find_element(By.XPATH, selector)
                    dropdown.click()
                    time.sleep(1)
                    print("  • Group dropdown opened")

                    # Try to find and click "Group" tab
                    try:
                        group_tab = self.driver.find_element(By.XPATH, "//*[contains(text(), 'Group')]")
                        group_tab.click()
                        time.sleep(1)
                        print("  • 'Group' tab clicked")
                    except NoSuchElementException:
                        print("  • No 'Group' tab found, continuing...")

                    # Try to select the target group
                    try:
                        target_option = self.driver.find_element(
                            By.XPATH,
                            f"//*[contains(text(), '{TARGET_GROUP}')]"
                        )
                        target_option.click()
                        time.sleep(2)
                        print(f"[OK] Switched to group: {TARGET_GROUP}")
                        return True
                    except NoSuchElementException:
                        print(f"  • Could not find group option: {TARGET_GROUP}")

                    break
                except NoSuchElementException:
                    continue

            # If we couldn't switch, check if we're still in the right group
            print("  • Verifying current group...")
            for selector in group_selectors:
                try:
                    element = self.driver.find_element(By.XPATH, selector)
                    if TARGET_GROUP in element.text:
                        print("[OK] In correct group")
                        return True
                except NoSuchElementException:
                    continue

            print("[WARN] Warning: Could not verify group, continuing anyway...")
            return True

        except Exception as e:
            print(f"[WARN] Group verification error: {e}")
            print("  Continuing anyway...")
            return True

    def expand_all_stores(self):
        """Expand all stores in the sidebar by clicking arrow icons"""
        print("\nStep 4: Expanding all stores in sidebar...")

        try:
            time.sleep(2)

            # Find all arrow/expansion icons in the tree structure
            # These are typically span elements with role="img" and aria-label="caret-down"
            arrow_selectors = [
                "//span[contains(@class, 'ant-tree-switcher') and contains(@class, 'ant-tree-switcher_close')]",
                "//span[@role='img' and @aria-label='caret-down']",
                "//span[contains(@class, 'switcher_close')]"
            ]

            arrows_found = []
            for selector in arrow_selectors:
                try:
                    arrows = self.driver.find_elements(By.XPATH, selector)
                    if arrows:
                        arrows_found = arrows
                        print(f"  • Found {len(arrows)} collapsed stores using selector")
                        break
                except NoSuchElementException:
                    continue

            if not arrows_found:
                print("[WARN] No collapsed stores found - they might already be expanded")
                return True

            # Click each arrow to expand
            expanded_count = 0
            for i, arrow in enumerate(arrows_found, 1):
                try:
                    # Scroll element into view
                    self.driver.execute_script("arguments[0].scrollIntoView(true);", arrow)
                    time.sleep(0.3)

                    # Click the arrow
                    arrow.click()
                    expanded_count += 1
                    print(f"  • Expanded store {i}/{len(arrows_found)}")
                    time.sleep(0.5)

                except Exception as e:
                    print(f"  [WARN] Could not click arrow {i}: {e}")
                    continue

            print(f"[OK] Expanded {expanded_count} stores")

            # Take screenshot after expansion
            self.take_screenshot("stores_expanded.png")

            return True

        except Exception as e:
            print(f"[ERROR] Error expanding stores: {e}")
            return False

    def get_parent_groups(self):
        """Get list of parent group names from the sidebar in visual order (top to bottom)"""
        print("\nStep 4: Getting parent groups from sidebar (top to bottom)...")

        try:
            time.sleep(2)

            # Find all tree nodes that have a switcher (arrow icon)
            # These are parent groups that can be expanded
            parent_nodes = self.driver.find_elements(
                By.XPATH,
                "//span[contains(@class, 'ant-tree-switcher') and not(contains(@class, 'ant-tree-switcher-noop'))]/following-sibling::span[@title]"
            )

            parent_groups = []
            seen = set()

            for node in parent_nodes:
                title = node.get_attribute('title')
                # Only add if it's a parent (no '@') and we haven't seen it yet
                if title and '@' not in title and title not in seen:
                    parent_groups.append(title)
                    seen.add(title)

            print(f"[OK] Found {len(parent_groups)} parent groups in order:")
            for idx, group in enumerate(parent_groups, 1):
                print(f"  {idx}. {group}")

            return parent_groups

        except Exception as e:
            print(f"[ERROR] Error getting parent groups: {e}")
            return []

    def expand_parent_group(self, group_name):
        """Click the arrow icon to expand a parent group"""
        try:
            # Find the parent group's arrow/switcher icon
            # The arrow is usually before the title with class 'ant-tree-switcher'
            arrow_element = self.driver.find_element(
                By.XPATH,
                f"//span[@title='{group_name}']/preceding-sibling::span[contains(@class, 'ant-tree-switcher')]"
            )

            # Check if it's already expanded (has 'ant-tree-switcher_open' class)
            classes = arrow_element.get_attribute('class')
            if 'ant-tree-switcher_open' not in classes:
                self.driver.execute_script("arguments[0].scrollIntoView(true);", arrow_element)
                time.sleep(0.5)
                arrow_element.click()
                time.sleep(2)
                print(f"  ▼ Expanded parent group: {group_name}")
            else:
                print(f"  ▼ Parent group already expanded: {group_name}")
            return True
        except Exception as e:
            print(f"  [WARN] Could not expand parent group: {e}")
            return False

    def collapse_parent_group(self, group_name):
        """Click the arrow icon to collapse a parent group"""
        try:
            # Find the parent group's arrow/switcher icon
            arrow_element = self.driver.find_element(
                By.XPATH,
                f"//span[@title='{group_name}']/preceding-sibling::span[contains(@class, 'ant-tree-switcher')]"
            )

            # Check if it's expanded
            classes = arrow_element.get_attribute('class')
            if 'ant-tree-switcher_open' in classes:
                self.driver.execute_script("arguments[0].scrollIntoView(true);", arrow_element)
                time.sleep(0.5)
                arrow_element.click()
                time.sleep(1)
                print(f"  ▲ Collapsed parent group: {group_name}")
            return True
        except Exception as e:
            print(f"  [WARN] Could not collapse parent group: {e}")
            return False

    def get_child_stores_of_group(self, group_name):
        """Get all child stores under the currently selected parent group"""
        try:
            time.sleep(1)

            # Find all visible store elements with '@' in the name
            store_elements = self.driver.find_elements(
                By.XPATH,
                "//span[@title and contains(@class, 'ant-tree-node-content-wrapper')]"
            )

            child_stores = []
            for element in store_elements:
                store_name = element.get_attribute('title')
                # Child stores have '@' and should start with the parent group name
                if store_name and '@' in store_name and store_name.startswith(group_name.split()[0]):
                    if store_name not in [s['name'] for s in child_stores]:
                        child_stores.append({
                            'name': store_name,
                            'parent': group_name
                        })

            print(f"  • Found {len(child_stores)} child stores under {group_name}")
            return child_stores

        except Exception as e:
            print(f"  [ERROR] Error getting child stores: {e}")
            return []

    def select_store_by_name(self, store_name):
        """Click on a store to select it by finding it fresh"""
        try:
            # Find the store element fresh to avoid stale reference
            store_element = self.driver.find_element(
                By.XPATH,
                f"//span[@title='{store_name}' and contains(@class, 'ant-tree-node-content-wrapper')]"
            )
            self.driver.execute_script("arguments[0].scrollIntoView(true);", store_element)
            time.sleep(0.5)
            store_element.click()
            time.sleep(2)
            return True
        except Exception as e:
            print(f"  [WARN] Could not select store: {e}")
            return False

    def is_store_bound(self):
        """Check if the current store is bound to any platform"""
        try:
            # Look for "Go Bind" button - if present, store is NOT bound
            go_bind_buttons = self.driver.find_elements(By.XPATH, "//button[contains(., 'Go Bind')]")
            return len(go_bind_buttons) == 0
        except Exception:
            return False

    def set_page_size_100(self):
        """Set pagination to show 100 items per page"""
        try:
            # Find the page size selector (usually bottom right)
            page_size_selectors = [
                "//div[contains(@class, 'ant-pagination-options')]//div[contains(@class, 'ant-select')]",
                "//span[contains(@class, 'ant-select-selection-item') and contains(text(), '/')]"
            ]

            for selector in page_size_selectors:
                try:
                    page_size_dropdown = self.driver.find_element(By.XPATH, selector)
                    page_size_dropdown.click()
                    time.sleep(1)

                    # Select 100 option
                    option_100 = self.driver.find_element(By.XPATH, "//div[@title='100 / page'] | //div[contains(text(), '100')]")
                    option_100.click()
                    time.sleep(2)
                    print("  • Set page size to 100")
                    return True
                except NoSuchElementException:
                    continue

            print("  [WARN] Could not find page size selector")
            return False

        except Exception as e:
            print(f"  [WARN] Error setting page size: {e}")
            return False

    def get_current_platform_tab(self):
        """Get the currently active platform tab"""
        try:
            active_tab = self.driver.find_element(By.XPATH, "//div[contains(@class, 'ant-tabs-tab-active')]")
            return active_tab.text.lower()
        except:
            return "unknown"

    def switch_platform_tab(self, platform_name):
        """Switch to a specific platform tab (Grab, deliveroo, foodPanda)"""
        try:
            tab = self.driver.find_element(By.XPATH, f"//div[@role='tab' and contains(., '{platform_name}')]")
            tab.click()
            time.sleep(2)
            print(f"  • Switched to {platform_name} tab")
            return True
        except Exception as e:
            print(f"  [WARN] Could not switch to {platform_name} tab: {e}")
            return False

    def scroll_table_fully(self):
        """Scroll the table both horizontally and vertically to load all content"""
        try:
            # Find the scrollable table container
            script = """
                // Find the ant-table-body container
                var tableBody = document.querySelector('.ant-table-body');
                if (tableBody) {
                    // First scroll to the bottom to load all rows
                    var scrollHeight = tableBody.scrollHeight;
                    var scrollStep = 300;
                    var currentScroll = 0;

                    while (currentScroll < scrollHeight) {
                        tableBody.scrollTop = currentScroll;
                        currentScroll += scrollStep;
                    }
                    tableBody.scrollTop = scrollHeight;

                    // Then scroll to the right to load all columns
                    var scrollWidth = tableBody.scrollWidth;
                    currentScroll = 0;

                    while (currentScroll < scrollWidth) {
                        tableBody.scrollLeft = currentScroll;
                        currentScroll += scrollStep;
                    }
                    tableBody.scrollLeft = scrollWidth;

                    // Finally, scroll back to top-left
                    tableBody.scrollTop = 0;
                    tableBody.scrollLeft = 0;

                    return true;
                }
                return false;
            """
            self.driver.execute_script(script)
            time.sleep(2)
            print("  • Scrolled table to load all content")
            return True
        except Exception as e:
            print(f"  [WARN] Could not scroll table: {e}")
            return False

    def scrape_items_from_table(self, store_name, platform):
        """Scrape all items from the current table view"""
        items = []

        try:
            # Check if there's a "Go Bind" button for this platform
            if not self.is_store_bound():
                print(f"  [SKIP] {platform} not bound for this store")
                return items

            # Wait for table to load
            time.sleep(2)

            # Scroll the table to ensure all content is loaded
            self.scroll_table_fully()

            # Find all rows in the table
            rows = self.driver.find_elements(By.XPATH, "//tbody[@class='ant-table-tbody']/tr")

            if not rows:
                print(f"  [INFO] No items found for {platform}")
                return items

            print(f"  • Found {len(rows)} items on {platform}")

            for idx, row in enumerate(rows, 1):
                try:
                    # Use JavaScript to extract data from form inputs and text
                    item_data_js = self.driver.execute_script("""
                        var row = arguments[0];
                        var cells = row.querySelectorAll('td');

                        function getCellValue(cell) {
                            if (!cell) return '';

                            // Try to get value from input field first
                            var input = cell.querySelector('input');
                            if (input && input.value) return input.value.trim();

                            // Try to get value from select/dropdown
                            var select = cell.querySelector('select');
                            if (select && select.value) return select.value.trim();

                            // Try to get selected option text
                            var selectedOption = cell.querySelector('.ant-select-selection-item');
                            if (selectedOption && selectedOption.textContent) return selectedOption.textContent.trim();

                            // Fallback to text content
                            var text = cell.innerText || cell.textContent || '';
                            return text.trim();
                        }

                        // Extract data from each column
                        var data = {
                            // Column 2: Image
                            image_url: (function() {
                                var img = cells[1] ? cells[1].querySelector('img') : null;
                                return img ? img.src : null;
                            })(),
                            // Column 3: Item name
                            item_name: getCellValue(cells[2]),
                            // Column 4: Size name
                            size_name: getCellValue(cells[3]),
                            // Column 5: Item type
                            item_type: getCellValue(cells[4]),
                            // Column 6: Menu group
                            menu_group: getCellValue(cells[5]),
                            // Column 7: SKU ID
                            sku_id: getCellValue(cells[6]),
                            // Column 8: Price
                            price: getCellValue(cells[7]),
                            // Column 9: Listing status (check if toggle is checked)
                            listing_status: (function() {
                                if (!cells[8]) return 'Unknown';
                                var toggle = cells[8].querySelector('.ant-switch-checked');
                                return toggle ? 'Listed' : 'Unlisted';
                            })()
                        };

                        return data;
                    """, row)

                    # Build item data object
                    item_data = {
                        'store_name': store_name,
                        'platform': platform,
                        'item_number': idx,
                        'image_url': item_data_js.get('image_url'),
                        'item_name': item_data_js.get('item_name', ''),
                        'size_name': item_data_js.get('size_name', ''),
                        'item_type': item_data_js.get('item_type', ''),
                        'menu_group': item_data_js.get('menu_group', ''),
                        'sku_id': item_data_js.get('sku_id', ''),
                        'price': item_data_js.get('price', ''),
                        'listing_status': item_data_js.get('listing_status', 'Unknown')
                    }

                    # Skip if no item name (likely a header or empty row)
                    if not item_data['item_name'] or item_data['item_name'] == 'Operation':
                        continue

                    items.append(item_data)

                except Exception as e:
                    print(f"  [WARN] Error scraping row {idx}: {e}")
                    continue

            print(f"  [OK] Scraped {len(items)} items from {platform}")

        except Exception as e:
            print(f"  [ERROR] Error scraping items: {e}")

        return items

    def scrape_store(self, store_name):
        """Scrape all items from all platforms for a specific store"""
        print(f"\n--- Scraping: {store_name} ---")

        all_items = []

        # Select the store by name (avoids stale element issues)
        if not self.select_store_by_name(store_name):
            print(f"[SKIP] Could not select store")
            return all_items

        # Check if store is bound to any platform
        if not self.is_store_bound():
            print(f"[SKIP] Store not bound to any platform")
            return all_items

        # Set page size to 100
        self.set_page_size_100()

        # Scrape from all 3 platform tabs
        platforms = ['Grab', 'deliveroo', 'foodPanda']

        for platform in platforms:
            # Switch to platform tab
            if self.switch_platform_tab(platform):
                # Check if this specific platform is bound
                if self.is_store_bound():
                    # Set page size again for this tab
                    self.set_page_size_100()

                    # Scrape items
                    items = self.scrape_items_from_table(store_name, platform)
                    all_items.extend(items)
                else:
                    print(f"  [SKIP] {platform} not bound")

        print(f"[OK] Total items scraped: {len(all_items)}")
        return all_items

    def save_items_to_json(self, all_items, filename="scraped_items.json"):
        """Save scraped items to JSON file"""
        try:
            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(all_items, f, indent=2, ensure_ascii=False)
            print(f"\n[OK] Saved {len(all_items)} items to {filename}")
        except Exception as e:
            print(f"\n[ERROR] Could not save to JSON: {e}")

    def take_screenshot(self, filename):
        """Take a screenshot for debugging"""
        try:
            filepath = os.path.join(os.getcwd(), filename)
            self.driver.save_screenshot(filepath)
            print(f"  [SCREENSHOT] Screenshot saved: {filename}")
        except Exception as e:
            print(f"  [WARN] Screenshot failed: {e}")

    def run(self):
        """Main execution flow"""
        print("="*70)
        print("BULLETPROOF ITEMS SCRAPER")
        print("="*70)

        try:
            # Setup
            if not self.connect_database():
                return False

            self.setup_driver()

            # Step 1: Login
            if not self.login():
                self.take_screenshot("error_login.png")
                return False

            # Step 2: Navigate to page
            if not self.navigate_to_takeaway_mapping():
                self.take_screenshot("error_navigation.png")
                return False

            # Step 3: Ensure correct group
            if not self.ensure_correct_group():
                self.take_screenshot("error_group_selection.png")
                return False

            # Step 4: Don't expand all - we'll expand one at a time
            time.sleep(2)

            # Take success screenshot
            self.take_screenshot("success_ready_to_scrape.png")

            print("\n" + "="*70)
            print("[OK] SETUP COMPLETE - READY TO SCRAPE")
            print("="*70)

            # Step 4: Get parent groups (without expanding)
            parent_groups = self.get_parent_groups()

            if not parent_groups:
                print("[ERROR] No parent groups found")
                return False

            # Step 6: Scrape stores group by group
            print(f"\n{'='*70}")
            print(f"STARTING SCRAPING PROCESS - {len(parent_groups)} PARENT GROUPS")
            print(f"{'='*70}")

            all_scraped_items = []
            total_scraped_count = 0
            total_skipped_count = 0

            for group_idx, parent_group in enumerate(parent_groups, 1):
                print(f"\n{'='*70}")
                print(f"[GROUP {group_idx}/{len(parent_groups)}] Processing: {parent_group}")
                print(f"{'='*70}")

                # Expand the parent group by clicking its arrow
                if not self.expand_parent_group(parent_group):
                    print(f"[SKIP] Could not expand parent group: {parent_group}")
                    continue

                # Get all child stores under this parent
                child_stores = self.get_child_stores_of_group(parent_group)

                if not child_stores:
                    print(f"[INFO] No child stores found for {parent_group}")
                    # Collapse before moving to next
                    self.collapse_parent_group(parent_group)
                    continue

                # Scrape each child store
                for store_idx, store in enumerate(child_stores, 1):
                    print(f"\n  [{store_idx}/{len(child_stores)}] Processing: {store['name']}")

                    try:
                        items = self.scrape_store(store['name'])

                        if items:
                            all_scraped_items.extend(items)
                            total_scraped_count += 1
                        else:
                            total_skipped_count += 1

                    except Exception as e:
                        print(f"  [ERROR] Failed to scrape {store['name']}: {e}")
                        total_skipped_count += 1
                        continue

                # Save progress after each group
                self.save_items_to_json(all_scraped_items, f"scraped_items_group_{group_idx}_{parent_group.replace(' ', '_')}.json")

                # Collapse the parent group after finishing all its children
                self.collapse_parent_group(parent_group)

            # Final save
            self.save_items_to_json(all_scraped_items, "scraped_items_final.json")

            print(f"\n{'='*70}")
            print(f"SCRAPING COMPLETE")
            print(f"{'='*70}")
            print(f"Total parent groups processed: {len(parent_groups)}")
            print(f"Stores scraped: {total_scraped_count}")
            print(f"Stores skipped: {total_skipped_count}")
            print(f"Total items collected: {len(all_scraped_items)}")
            print(f"{'='*70}")

            # Auto-import to database
            print("\n[IMPORT] Starting automatic database import...")
            import subprocess
            import_result = subprocess.run(
                ['php', 'import_scraped_items.php'],
                capture_output=True,
                text=True,
                cwd=os.path.dirname(os.path.abspath(__file__))
            )

            if import_result.returncode == 0:
                print("[IMPORT] Successfully imported items to database!")
                print(import_result.stdout)
            else:
                print(f"[IMPORT ERROR] Failed to import: {import_result.stderr}")

            # Keep browser open for inspection
            print("\nBrowser will remain open for 30 seconds...")
            time.sleep(30)

            return True

        except Exception as e:
            print(f"\n[ERROR] Fatal error: {e}")
            self.take_screenshot("error_fatal.png")
            return False

        finally:
            # Cleanup
            if self.cursor:
                self.cursor.close()
            if self.db:
                self.db.close()
            if self.driver:
                self.driver.quit()

if __name__ == "__main__":
    scraper = BulletproofItemsScraper()
    success = scraper.run()
    sys.exit(0 if success else 1)
