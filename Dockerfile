FROM php:8.4-fpm

# 必要なパッケージインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能インストール
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Composerインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP設定ファイルコピー
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# 作業ディレクトリ設定
WORKDIR /var/www

# Composer設定ファイルを先にコピー（キャッシュ効率化）
COPY composer.json composer.lock ./

# Composer依存関係インストール
RUN composer install --no-scripts --no-autoloader --no-dev

# アプリケーションファイルコピー
COPY . /var/www

# Composer最終処理
RUN composer dump-autoload --optimize --no-dev

# 起動スクリプトコピー
COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 権限設定
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

