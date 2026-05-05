#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Running migrations..."
php artisan migrate --force

echo "Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
