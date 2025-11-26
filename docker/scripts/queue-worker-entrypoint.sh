#!/bin/bash
set -e

echo "=== Starting Queue Worker Container Setup ==="

cd /var/www || exit 1

# Composer install (if needed)
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Laravel setup
echo "Caching Laravel configuration..."
php artisan config:cache

echo "=== Starting Supervisor (Queue Worker) ==="

# Supervisorを起動（queue-worker用の設定ファイルを使用）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord-queue.conf

