#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force
echo "Seeding data..."
php artisan db:seed --force

# Reset AI insight reports and caches so a fresh generation runs after deploy
php artisan insights:reset --no-interaction

echo "Clearing cache..."
php artisan cache:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:cache
