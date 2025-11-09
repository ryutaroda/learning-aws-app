#!/bin/bash
set -e

echo "=== Starting Laravel Application Setup ==="

# Composer install
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Laravel setup
echo "Caching Laravel configuration..."
# ECSでは環境変数（environmentFiles）でAPP_KEYが設定されるため key:generate は不要
# php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database migration (if needed)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

echo "=== Starting Supervisor (Nginx + PHP-FPM) ==="

# Start supervisord in background
"$@" &
SUPERVISOR_PID=$!

# Wait for services to start
echo "Waiting for services to initialize..."
sleep 5

# Test health check endpoint after startup
echo "=== Testing Health Check Endpoint ==="
MAX_RETRIES=6
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if wget --timeout=10 --tries=1 -O- http://localhost/up 2>&1; then
        echo "=== Health Check PASSED at $(date) ==="
        break
    else
        RETRY_COUNT=$((RETRY_COUNT + 1))
        if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
            echo "=== Health check attempt $RETRY_COUNT failed, retrying in 5s... ==="
            sleep 5
        else
            echo "=== Health Check FAILED after $MAX_RETRIES attempts at $(date) ==="
            echo "=== Checking Service Status ==="
            ps aux | grep -E 'nginx|php-fpm|supervisord' || true
            echo "=== Checking Nginx Error Log ==="
            tail -n 20 /var/log/nginx/error.log 2>/dev/null || echo "No nginx error log found"
            echo "=== Environment Variables ==="
            echo "DB_HOST: ${DB_HOST:-not set}"
            echo "DB_DATABASE: ${DB_DATABASE:-not set}"
            echo "DB_USERNAME: ${DB_USERNAME:-not set}"
            echo "DB_PASSWORD: ${DB_PASSWORD:+[REDACTED]}"
            echo "APP_KEY: ${APP_KEY:+[REDACTED]}"
        fi
    fi
done

echo "=== Application Ready ==="

# Keep the script running and wait for supervisor
wait $SUPERVISOR_PID

