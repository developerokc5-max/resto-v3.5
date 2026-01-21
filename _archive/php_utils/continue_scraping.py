#!/usr/bin/env python3
"""Continue scraping from where we left off"""

import os
import sys

# Get list of already processed stores
screenshots_dir = "store_screenshots"
processed_stores = []

if os.path.exists(screenshots_dir):
    for folder in os.listdir(screenshots_dir):
        # Extract store name from folder (remove number prefix)
        parts = folder.split('_', 1)
        if len(parts) == 2:
            store_name = parts[1].replace('at', '@')
            processed_stores.append(store_name)

print(f"Already processed {len(processed_stores)} stores:")
for store in processed_stores:
    print(f"  - {store}")

print(f"\nStarting from store {len(processed_stores) + 1}...")

# Now import and run the scraper
from scrape_items_real import main
main()
