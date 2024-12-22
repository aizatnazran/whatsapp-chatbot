#!/bin/bash

# Debug: Show current directory structure
echo "Current directory structure:"
ls -la /var/www
echo "Public directory contents:"
ls -la /var/www/public

# Fix permissions
chown -R www-data:www-data /var/www
chmod -R 755 /var/www/storage
chmod -R 755 /var/www/bootstrap/cache

# Create Laravel storage structure
mkdir -p /var/www/storage/framework/{sessions,views,cache}
mkdir -p /var/www/storage/logs
chown -R www-data:www-data /var/www/storage

# Debug: Show PHP-FPM version and modules
php -v
php -m

# Start PHP-FPM
php-fpm -D

# Debug: Test Nginx configuration
nginx -t

# Start Nginx
nginx -g "daemon off;"
