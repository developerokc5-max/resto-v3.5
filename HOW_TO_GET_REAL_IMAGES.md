# 🖼️ How to Get Real Item Images from RestoSuite BO

**Date:** 2025-12-30
**Status:** Step-by-step guide

---

## Method 1: Copy from RestoSuite Back Office (Manual)

### **Steps:**

1. **Login to RestoSuite BO:**
   ```
   https://bo.sea.restosuite.ai
   ```

2. **Navigate to Item Mapping:**
   - Click "Brands" tab
   - Select store (e.g., "Le Le Mee Pok @ Toa Payoh")
   - Click "Query" or "Publish"
   - You'll see items with images

3. **Export Item Data:**
   - Open Browser DevTools (F12)
   - Go to "Network" tab
   - Look for API call to `itemBindingList`
   - Right-click → Copy → Copy Response
   - Save to file: `items_with_images.json`

4. **Import to Your System:**
   ```bash
   php artisan import:images-json items_with_images.json
   ```

---

## Method 2: One-by-One Manual Copy (Fastest)

### **For Each Store:**

1. Open RestoSuite BO → Select Store
2. For each item you see with an image:
   - Right-click image → "Copy image address"
   - You'll get URL like:
     ```
     https://resto-images-sg.s3.ap-southeast-1.amazonaws.com/c40000210/...
     ```

3. Update in your database:
   ```bash
   php artisan tinker

   DB::table('restosuite_item_snapshots')
     ->where('name', 'Signature BCM')
     ->where('shop_id', '402951243')
     ->update([
       'image_url' => 'https://resto-images-sg.s3.ap-southeast-1.amazonaws.com/...',
       'updated_at' => now()
     ]);
   ```

---

## Method 3: Contact RestoSuite Support

### **Ask them for:**

**Email to:** support@restosuite.ai

**Subject:** OpenAPI - Request for Item Image URLs Endpoint

**Message:**
```
Hi RestoSuite Team,

We're using your OpenAPI (Corporation ID: 400000210) and would like to
access item images programmatically.

Current endpoint /api/v1/bo/menu/queryItemList doesn't include itemImageUrl.

Could you please provide:
1. The correct API endpoint that includes item images
2. Documentation for image URL field
3. Any additional permissions needed

Thank you!
```

---

## Method 4: Use Sample Images (Current Working Solution)

**Already Implemented!**

We're using placeholder images from Unsplash CDN:

```bash
# Add more sample images
php artisan tinker

# Chicken items
DB::table('restosuite_item_snapshots')
  ->where('name', 'LIKE', '%Chicken%')
  ->whereNull('image_url')
  ->limit(20)
  ->update([
    'image_url' => 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=400',
    'updated_at' => now()
  ]);

# Rice items
DB::table('restosuite_item_snapshots')
  ->where('name', 'LIKE', '%Rice%')
  ->whereNull('image_url')
  ->limit(20)
  ->update([
    'image_url' => 'https://images.unsplash.com/photo-1516684732162-798a0062be99?w=400',
    'updated_at' => now()
  ]);

# Noodle items
DB::table('restosuite_item_snapshots')
  ->where('name', 'LIKE', '%Mee%')
  ->whereNull('image_url')
  ->limit(20)
  ->update([
    'image_url' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400',
    'updated_at' => now()
  ]);
```

---

## Current Status

**✅ Working Now:**
- Image system fully functional
- 30 items with real sample images
- Smart emoji fallbacks for other items
- NO MORE ugly letter avatars!

**View it:**
```
http://127.0.0.1:8000/items
```

---

## Recommendation

**Best approach for now:**

1. **Keep using sample images** (looks great!)
2. **Contact RestoSuite support** (get proper API access)
3. **OR manually copy** images from BO for important items

**The system is ready** - as soon as you get real image URLs from RestoSuite (either via API or manual copy), just run:

```bash
php artisan import:images-json your_export.json
```

And all images will update instantly!

---

**Created by:** Claude Code
**Date:** 2025-12-30
**Status:** ✅ System Ready, Waiting for Real Image URLs
