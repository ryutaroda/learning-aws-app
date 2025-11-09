#!/bin/bash
set -e

# Composer install
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Laravel setup
# ECSでは環境変数（environmentFiles）でAPP_KEYが設定されるため key:generate は不要
# php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database migration (if needed)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

# Start PHP-FPM
exec "$@"

