#!/bin/bash

# Fix permissions
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

# Set Laravel environment
export APP_ENV=local
export APP_DEBUG=true

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Run migrations if needed
php artisan migrate --force

# Start Laravel queue worker
php artisan queue:work &

# Start Laravel scheduler
php artisan schedule:work &

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
