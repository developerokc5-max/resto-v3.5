from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

# Setup Chrome
options = webdriver.ChromeOptions()
options.add_argument('--start-maximized')
driver = webdriver.Chrome(options=options)

try:
    # Login
    driver.get('https://bo.ca.restosuite.ai/takeaway-product-mapping')
    time.sleep(3)

    email = driver.find_element(By.ID, 'email')
    email.send_keys('okchickenrice2018@gmail.com')
    driver.find_element(By.XPATH, "//button[contains(., 'Next')]").click()
    time.sleep(2)

    password = driver.find_element(By.ID, 'password')
    password.send_keys('Abcd1234!')
    driver.find_element(By.XPATH, "//button[@type='submit']").click()
    time.sleep(5)

    # Navigate to page
    driver.get('https://bo.ca.restosuite.ai/takeaway-product-mapping')
    time.sleep(3)

    # Expand first group and select first store
    groups = driver.find_elements(By.XPATH, "//div[contains(@class, 'ant-tree-treenode')]")
    if groups:
        groups[0].click()
        time.sleep(2)

    stores = driver.find_elements(By.XPATH, "//span[@class='ant-tree-title']")
    if len(stores) > 1:
        stores[1].click()
        time.sleep(3)

    # Debug: Print table structure
    print("\n=== TABLE STRUCTURE DEBUG ===\n")

    # Get first row
    rows = driver.find_elements(By.XPATH, "//tbody[@class='ant-table-tbody']/tr")
    print(f"Found {len(rows)} rows")

    if rows:
        first_row = rows[0]

        # Method 1: Get all cells
        cells = first_row.find_elements(By.TAG_NAME, "td")
        print(f"\nRow has {len(cells)} cells")

        # Method 2: JavaScript extraction
        cell_data = driver.execute_script("""
            var row = arguments[0];
            var cells = row.querySelectorAll('td');
            var result = [];
            for (var i = 0; i < cells.length; i++) {
                result.push({
                    index: i,
                    innerText: cells[i].innerText,
                    textContent: cells[i].textContent,
                    innerHTML: cells[i].innerHTML.substring(0, 200)
                });
            }
            return result;
        """, first_row)

        print("\n=== CELL DATA ===")
        for cell_info in cell_data:
            print(f"\nCell {cell_info['index']}:")
            print(f"  innerText: {cell_info['innerText'][:100]}")
            print(f"  textContent: {cell_info['textContent'][:100]}")
            print(f"  innerHTML: {cell_info['innerHTML'][:100]}")

        # Check for image
        img_src = driver.execute_script("""
            var row = arguments[0];
            var img = row.querySelector('img');
            return img ? img.src : 'NO IMAGE FOUND';
        """, first_row)
        print(f"\nImage URL: {img_src}")

finally:
    input("\nPress Enter to close browser...")
    driver.quit()
