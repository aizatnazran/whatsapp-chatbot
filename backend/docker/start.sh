#!/bin/bash

# Set Laravel environment
export APP_ENV=local
export APP_DEBUG=true

# Start Laravel queue worker
php artisan queue:work &

# Start Laravel scheduler
php artisan schedule:work &

# Enable Laravel logging
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
