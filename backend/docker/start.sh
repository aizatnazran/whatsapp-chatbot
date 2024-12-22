#!/bin/bash

# Fix permissions
chown -R www-data:www-data /var/www
chmod -R 755 /var/www/storage
chmod -R 755 /var/www/bootstrap/cache

# Ensure storage directory exists
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/storage/logs

# Set Laravel environment
export APP_ENV=local
export APP_DEBUG=true

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
