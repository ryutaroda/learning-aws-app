#!/bin/bash
set -e

echo "=== Starting Queue Worker Container Setup ==="

# テスト用: 環境変数 SIMULATE_FIRST_FAILURE=true が設定されている場合、初回起動のみ失敗
if [ "${SIMULATE_FIRST_FAILURE:-false}" = "true" ]; then
    echo "⚠️  TEST MODE: Simulating first startup failure..."
    echo "This is intentional for testing restart policy."
    echo "To disable this, remove SIMULATE_FIRST_FAILURE environment variable from task definition."
    exit 1
fi

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

