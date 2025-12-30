# 🚀 RESTO DB V3 - SETUP INSTRUCTIONS

**Hybrid Monitoring System (API + Scraping)**
**Version:** 2.0.0
**Laravel:** 12.x
**PHP:** 8.2+

---

## 📋 PREREQUISITES

- PHP 8.2 or higher
- Composer
- Node.js & npm (for frontend assets)
- SQLite extension enabled
- Git

---

## ⚡ QUICK START

### **1. Clone Repository**
```bash
git clone <your-repo-url>
cd resto-db-v3
```

### **2. Install Dependencies**
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### **3. Environment Setup**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env and add your RestoSuite credentials:
# RESTOSUITE_APP_KEY=your_app_key_here
# RESTOSUITE_SECRET_KEY=your_secret_key_here
# RESTOSUITE_CORP_ID=your_corporation_id_here
```

### **4. Database Setup**
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Or use the automated setup
php artisan migrate --force
```

### **5. Initial Data Sync**
```bash
# Sync menu items from RestoSuite API
php artisan restosuite:sync-items --page=1 --size=100

# Scrape platform status (Grab, FoodPanda, Deliveroo)
php artisan scrape:platform-status --limit=38
```

### **6. Start Development Server**
```bash
php artisan serve
```

Visit: `http://localhost:8000`

---

## 🌐 AVAILABLE PAGES

- **Dashboard:** `http://localhost:8000/dashboard`
- **Stores:** `http://localhost:8000/stores`
- **Items:** `http://localhost:8000/items`
- **Platforms (NEW!):** `http://localhost:8000/platforms`
- **History:** `http://localhost:8000/item-tracking`

---

## 🔌 API ENDPOINTS

### **Platform Status**
```bash
# Get all platform statuses
GET /api/platform/status

# Get specific shop status
GET /api/platform/status/{shopId}

# Get statistics
GET /api/platform/stats

# Get online platforms
GET /api/platform/online

# Get offline platforms
GET /api/platform/offline

# Get stale data
GET /api/platform/stale
```

### **Sync & Management**
```bash
# Trigger manual scraping
POST /api/sync/scrape
Body: {"limit": 15, "platform": "grab"}

# Clear cache
POST /api/sync/clear-cache

# Health check
GET /api/health
```

### **Example API Calls**
```bash
# Check system health
curl http://localhost:8000/api/health

# Get platform statistics
curl http://localhost:8000/api/platform/stats

# Trigger scraping
curl -X POST http://localhost:8000/api/sync/scrape \
  -H "Content-Type: application/json" \
  -d '{"limit": 10}'
```

---

## ⚙️ AUTOMATION SETUP

### **Option 1: Using Laravel Scheduler (Recommended)**

Add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler will automatically:
- Sync API data every 5 minutes
- Scrape platform status every 10 minutes

### **Option 2: Using External Cron (cron-job.org)**

Create two jobs:

**Job 1: API Sync**
- URL: `https://your-domain.com/api/sync/api`
- Schedule: Every 5 minutes

**Job 2: Platform Scraping**
- URL: `https://your-domain.com/api/sync/scrape`
- Schedule: Every 10 minutes

---

## 🧪 TESTING

### **Test API Sync**
```bash
php artisan restosuite:sync-items --page=1 --size=10
```

**Expected Output:**
```
✅ Sync completed successfully
Shops processed: 10
Items synced: 150+
Changes detected: XX
```

### **Test Platform Scraping**
```bash
php artisan scrape:platform-status --limit=5
```

**Expected Output:**
```
🔍 Starting platform status scraping...
Found 5 shops to scrape
 5/5 [============================] 100%

✅ Scraping completed!
Shops Scraped: 5
Errors: 0
Success Rate: 100%
```

### **Test Routes**
```bash
# List all routes
php artisan route:list

# Test API routes
php artisan route:list --path=api

# Test web routes
php artisan route:list --path=platforms
```

### **Test Database**
```bash
# Check platform status count
php artisan tinker --execute="echo App\Models\PlatformStatus::count();"

# Expected: 114 (38 shops × 3 platforms)
```

---

## 🐳 DOCKER DEPLOYMENT (Optional)

### **Using Docker Compose**
```bash
# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec web php artisan migrate

# Run initial sync
docker-compose exec web php artisan restosuite:sync-items
docker-compose exec web php artisan scrape:platform-status --limit=38
```

Access: `http://localhost:9000`

---

## 🌍 PRODUCTION DEPLOYMENT

### **Environment Configuration**
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use proper database
DB_CONNECTION=sqlite
DB_DATABASE=/var/data/database.sqlite

# Set cache driver
CACHE_STORE=file  # or redis/memcached
```

### **Deployment Steps**
```bash
# 1. Clone repository
git clone <repo-url>

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Run migrations
php artisan migrate --force

# 5. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Setup cron
crontab -e
# Add: * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# 8. Start services
php artisan serve --host=0.0.0.0 --port=8000
# Or configure with Nginx/Apache
```

---

## 📊 MONITORING

### **Check System Health**
```bash
# Via CLI
php artisan tinker --execute="
echo 'Shops: ' . DB::table('platform_status')->distinct('shop_id')->count() . PHP_EOL;
echo 'Platforms Online: ' . DB::table('platform_status')->where('is_online', 1)->count() . PHP_EOL;
echo 'Last Sync: ' . DB::table('platform_status')->max('last_checked_at') . PHP_EOL;
"

# Via API
curl http://localhost:8000/api/health
```

### **View Logs**
```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Search for errors
grep -i error storage/logs/laravel.log

# Check scraping logs
grep "Platform scraping" storage/logs/laravel.log
```

---

## 🔧 TROUBLESHOOTING

### **Problem: Routes not found**
```bash
# Clear route cache
php artisan route:clear
php artisan optimize:clear

# Re-cache
php artisan route:cache
```

### **Problem: Database not found**
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### **Problem: API endpoints return 404**
```bash
# Verify bootstrap/app.php includes:
# api: __DIR__.'/../routes/api.php',

# Clear and recache
php artisan optimize:clear
```

### **Problem: Scraping shows 0 shops**
```bash
# Check environment variables
php artisan tinker --execute="
echo 'APP_KEY: ' . (config('restosuite.app_key') ?: 'MISSING') . PHP_EOL;
echo 'CORP_ID: ' . (config('restosuite.corporation_id') ?: 'MISSING') . PHP_EOL;
"

# Run sync first to populate shops
php artisan restosuite:sync-items --page=1 --size=100
```

### **Problem: Permission denied**
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite
```

---

## 📚 DOCUMENTATION

- **HYBRID_SYSTEM_README.md** - Complete technical documentation
- **UPGRADE_COMPLETE.md** - Upgrade summary and new features
- **SETUP.md** - This file (setup instructions)

---

## 🎯 VERIFICATION CHECKLIST

Before considering setup complete, verify:

- [ ] ✅ Dashboard loads at `/dashboard`
- [ ] ✅ Platforms page loads at `/platforms`
- [ ] ✅ API health check returns JSON: `/api/health`
- [ ] ✅ Platform status API works: `/api/platform/stats`
- [ ] ✅ Manual sync works: `php artisan restosuite:sync-items`
- [ ] ✅ Manual scrape works: `php artisan scrape:platform-status --limit=5`
- [ ] ✅ Database has data: Check platform_status table
- [ ] ✅ Scheduler configured: `php artisan schedule:list`
- [ ] ✅ No errors in logs: `tail storage/logs/laravel.log`

---

## 🚀 NEXT STEPS

After setup:

1. **Review Dashboard** - Check if data looks correct
2. **Test Manual Scraping** - Run `scrape:platform-status`
3. **Setup Automation** - Configure cron or external scheduler
4. **Monitor Performance** - Watch logs for any issues
5. **Deploy to Production** - Follow production deployment steps

---

## 🆘 GETTING HELP

If you encounter issues:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Verify environment: `php artisan about`
3. Test database: `php artisan tinker`
4. Clear caches: `php artisan optimize:clear`
5. Check documentation: HYBRID_SYSTEM_README.md

---

## 📝 SYSTEM INFORMATION

**Database Tables:**
- `platform_status` - Platform status data (NEW!)
- `restosuite_item_snapshots` - Menu item snapshots
- `restosuite_item_changes` - Change history
- `users`, `cache`, `jobs`, etc.

**Artisan Commands:**
- `restosuite:sync-items` - Sync menu items from API
- `restosuite:sync-safe` - Safe sync with rate limiting
- `scrape:platform-status` - Scrape platform status (NEW!)

**Models:**
- `App\Models\PlatformStatus` - Platform status model (NEW!)

**Services:**
- `App\Services\PlatformScrapingService` - Scraping logic (NEW!)

---

**Setup Complete! Your hybrid monitoring system is ready to use!** 🎉

**Version:** 2.0.0 (Hybrid System)
**Built with:** Laravel 12, PHP 8.2, SQLite
**Features:** API Sync + Platform Scraping
