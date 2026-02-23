#!/usr/bin/env bash
set -e

composer install --no-dev --optimize-autoloader

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force

npm ci
npm run build
