#!/bin/sh
set -e

# Ensure Laravel storage and cache subdirectories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Guarantee permissive write access for local development across Windows/WSL2 mounts
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Execute the main container command (e.g., php-fpm, php artisan queue:work, etc.)
exec "$@"
