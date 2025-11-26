#!/usr/bin/env bash
# Render.com build script for Laravel
# exit on error
set -o errexit

echo "Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

echo "Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Build completed successfully!"
