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

# テスト用: 環境変数 SIMULATE_FIRST_FAILURE=true が設定されている場合、初回起動のみ失敗
if [ "${SIMULATE_FIRST_FAILURE:-false}" = "true" ]; then
    STARTED_FLAG="/tmp/queue-worker-started"
    if [ ! -f "$STARTED_FLAG" ]; then
        echo "⚠️  TEST MODE: Simulating first startup failure..."
        touch "$STARTED_FLAG"

        # ヘルスチェックサーバーを起動するためにSupervisorをバックグラウンドで起動
        /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord-queue.conf &
        sleep 10

        # restartAttemptPeriodを満たすために待機
        sleep 65
        exit 1
    fi
fi

echo "=== Starting Supervisor (Queue Worker) ==="

# Supervisorを起動（queue-worker用の設定ファイルを使用）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord-queue.conf

