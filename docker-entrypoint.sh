#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# The persistent disk is mounted at /var/www/html/database
# This hides database/migrations/ from the image, so we restore them first
echo "🗄️ Restoring migration files hidden by disk mount..."
if [ -d /var/www/html/database_image/migrations ]; then
    cp -rn /var/www/html/database_image/migrations /var/www/html/database/migrations 2>/dev/null || true
    cp -rn /var/www/html/database_image/factories /var/www/html/database/factories 2>/dev/null || true
    cp -rn /var/www/html/database_image/seeders /var/www/html/database/seeders 2>/dev/null || true
fi

DISK_DB="/var/www/html/database/database.sqlite"
SEED_DB="/var/www/html/database_image/database.sqlite"

if [ -f "$DISK_DB" ]; then
    SHOP_COUNT=$(sqlite3 "$DISK_DB" "SELECT COUNT(*) FROM shops;" 2>/dev/null || echo "0")
    echo "📊 Found $SHOP_COUNT shops in persistent database"
    if [ "$SHOP_COUNT" = "0" ] && [ -f "$SEED_DB" ]; then
        echo "🌱 Seeding from bundled database..."
        cp "$SEED_DB" "$DISK_DB"
        echo "✅ Database seeded"
    fi
else
    echo "📦 First boot - copying bundled database to persistent disk..."
    if [ -f "$SEED_DB" ]; then
        cp "$SEED_DB" "$DISK_DB"
        echo "✅ Database copied to disk"
    else
        touch "$DISK_DB"
    fi
fi

chown -R www-data:www-data /var/www/html/database

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Clear caches
echo "⚡ Clearing Laravel caches..."
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Laravel setup complete!"

# Run scrapers sequentially to stay within 512MB RAM limit
# Platform finishes → Items runs → repeat (only 1 Chromium at a time)
echo "🔄 Starting scraper loop (sequential)..."
(while true; do
    python3 /var/www/html/platform-test-trait-1/scrape_platform_sync.py >> /var/www/html/storage/logs/platform_scraper.log 2>&1
    python3 /var/www/html/item-test-trait-1/scrape_items_sync_v2.py >> /var/www/html/storage/logs/items_scraper.log 2>&1
done) &

exec "$@"
