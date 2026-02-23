#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database file to be ready
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "📦 Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Copy seeded database if it exists and target is empty
echo "🗄️ Checking database..."
if [ -f /var/www/html/database/database.sqlite ]; then
    SHOP_COUNT=$(sqlite3 /var/www/html/database/database.sqlite "SELECT COUNT(*) FROM shops;" 2>/dev/null || echo "0")
    echo "📊 Found $SHOP_COUNT shops in database"
else
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Clear caches
echo "⚡ Clearing Laravel caches..."
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
chown -R www-data:www-data /var/www/html/storage
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Laravel setup complete!"

# Execute the CMD (apache2-foreground)
exec "$@"
