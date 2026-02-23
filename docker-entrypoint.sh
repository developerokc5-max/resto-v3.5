#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Persistent disk is mounted at /data (NOT inside /var/www/html/database)
# This avoids hiding the database/migrations/ folder
echo "🗄️ Checking persistent database..."
DISK_DB="/data/database.sqlite"
SEED_DB="/var/www/html/database/database.sqlite"

mkdir -p /data

if [ -f "$DISK_DB" ]; then
    SHOP_COUNT=$(sqlite3 "$DISK_DB" "SELECT COUNT(*) FROM shops;" 2>/dev/null || echo "0")
    echo "📊 Found $SHOP_COUNT shops in persistent database"
    if [ "$SHOP_COUNT" = "0" ]; then
        echo "🌱 Seeding from bundled database..."
        cp "$SEED_DB" "$DISK_DB"
        echo "✅ Database seeded"
    fi
else
    echo "📦 First boot - copying bundled database to persistent disk..."
    cp "$SEED_DB" "$DISK_DB"
    echo "✅ Database copied to /data/database.sqlite"
fi

chown -R www-data:www-data /data

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

exec "$@"
