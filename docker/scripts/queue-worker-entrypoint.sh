#!/bin/bash
set -e

echo "=== Starting Queue Worker Container Setup ==="

# テスト用: 環境変数 SIMULATE_FIRST_FAILURE=true が設定されている場合、初回起動のみ失敗
# ファイルベースで初回起動を検知（restart policyによる再起動時は同じコンテナインスタンス内でファイルが残る可能性がある）
if [ "${SIMULATE_FIRST_FAILURE:-false}" = "true" ]; then
    STARTED_FLAG="/tmp/queue-worker-started"
    if [ ! -f "$STARTED_FLAG" ]; then
        echo "⚠️  TEST MODE: Simulating first startup failure..."
        echo "This is intentional for testing restart policy."
        echo "To disable this, remove SIMULATE_FIRST_FAILURE environment variable from task definition."
        touch "$STARTED_FLAG"

        # restartAttemptPeriodを満たすために、少し待機してから失敗
        # これにより、Restart Policyが正しく動作する
        echo "Waiting 65 seconds to satisfy restartAttemptPeriod (60 seconds)..."
        sleep 65
        exit 1
    else
        echo "✅ TEST MODE: Restart detected, starting normally..."
        # ファイルを削除して次回のテストに備える（オプション）
        # rm -f "$STARTED_FLAG"
    fi
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

