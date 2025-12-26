#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database file to be ready
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "📦 Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Clear and cache Laravel configs
echo "⚡ Caching Laravel configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel setup complete!"

# Execute the CMD (apache2-foreground)
exec "$@"
