#!/usr/bin/env bash
set -e

echo "==> Installing dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Preparing storage"
php artisan storage:link || true
php artisan config:clear || true
php artisan cache:clear || true

echo "==> Creating SQLite database if missing"
mkdir -p /var/data
if [ ! -f /var/data/database.sqlite ]; then
  touch /var/data/database.sqlite
fi

echo "==> Running migrations"
php artisan migrate --force

echo "==> Optimizing"
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true
