#!/usr/bin/env bash
set -e

composer install --no-dev --optimize-autoloader

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force

npm ci
npm run build

# Cache config + views for faster cold-start bootstrap
# (route:cache skipped — routes use closures)
php artisan config:cache
php artisan view:cache

# Pre-warm all DB-backed caches so first visitor after deploy gets fast responses
# (without this every page is a cold DB hit — 6-8s per page on Neon)
php artisan cache:warm