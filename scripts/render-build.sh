#!/bin/bash
set -e

echo "🚀 Starting Render build..."

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database file if it doesn't exist
if [ ! -f database/database.sqlite ]; then
    echo "📦 Creating SQLite database..."
    touch database/database.sqlite
fi

# Run Laravel migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Cache Laravel configuration
echo "⚡ Caching Laravel configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite

echo "✅ Build complete!"
