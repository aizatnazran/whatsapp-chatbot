#!/bin/bash

# Fix permissions
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

# Set Laravel environment
export APP_ENV=local
export APP_DEBUG=true

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
