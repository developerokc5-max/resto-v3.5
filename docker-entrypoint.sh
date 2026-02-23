#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Check if persistent disk database exists and has data
echo "🗄️ Checking persistent database..."
DISK_DB="/var/www/html/database/database.sqlite"
SEED_DB="/var/www/html/database/database.sqlite.seed"

# Copy our seeded database as a seed backup if not already there
if [ -f "$DISK_DB" ] && [ ! -f "$SEED_DB" ]; then
    cp "$DISK_DB" "$SEED_DB"
fi

# If disk database is empty (new disk), restore from seed
if [ -f "$DISK_DB" ]; then
    SHOP_COUNT=$(sqlite3 "$DISK_DB" "SELECT COUNT(*) FROM shops;" 2>/dev/null || echo "0")
    echo "📊 Found $SHOP_COUNT shops in database"
    if [ "$SHOP_COUNT" = "0" ] && [ -f "$SEED_DB" ]; then
        echo "🌱 Seeding database from backup..."
        cp "$SEED_DB" "$DISK_DB"
        echo "✅ Database seeded with existing data"
    fi
else
    echo "📦 Creating new database..."
    touch "$DISK_DB"
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

# Show .env contents (mask key) to confirm config loaded
echo "📄 Active .env config:"
grep -E "^(APP_|DB_|SESSION_|CACHE_)" /var/www/html/.env | grep -v "APP_KEY"

# Test Laravel can bootstrap at all
echo "🔍 Testing Laravel bootstrap..."
php artisan about --only=environment 2>&1 || echo "⚠️ Laravel bootstrap failed!"

# Execute the CMD (apache2-foreground)
exec "$@"
