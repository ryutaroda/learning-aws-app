#!/bin/bash
set -e

# タイムスタンプ付きログ出力関数
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [QUEUE-WORKER] $*"
}

# 起動ログ
log "=========================================="
log "Queue Worker Starting..."
log "PID: $$"
log "Command: php artisan queue:work sqs --sleep=3 --tries=3 --max-time=3600 --max-jobs=10"
log "=========================================="

# queue:workコマンドを実行
cd /var/www || exit 1

php artisan queue:work sqs --sleep=3 --tries=3 --max-time=3600 --max-jobs=10
EXIT_CODE=$?

# 終了ログ
log "=========================================="
log "Queue Worker Stopped"
log "Exit Code: $EXIT_CODE"
log "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
log "=========================================="

exit $EXIT_CODE

