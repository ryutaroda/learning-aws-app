# Laravel ECRデプロイメントドキュメント

## 作成順序

### 前提条件
1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`
3. ✅ **環境変数設定完了**: S3バケット・Secrets Manager

### 作成手順

#### 1. Laravelプロジェクト作成
- [ ] **ローカル環境準備**
  - PHP 8.x インストール確認
  - Composer インストール確認
  - Docker Desktop インストール確認

- [ ] **Laravelプロジェクト初期化**
  ```bash
  composer create-project laravel/laravel learning-aws-app
  cd learning-aws-app
  ```

- [ ] **基本設定**
  - .env ファイル設定
  - データベース接続設定
  - アプリケーションキー生成

#### 2. Docker環境構築
- [ ] **Dockerfile作成**
  - PHP-FPM ベースイメージ
  - 必要な拡張機能インストール
  - アプリケーションファイルコピー

- [ ] **docker-compose.yml作成**
  - Webサーバー（Nginx）
  - アプリケーション（PHP-FPM）
  - データベース（MySQL/PostgreSQL）

- [ ] **ローカル動作確認**
  ```bash
  docker-compose up -d
  ```

#### 3. AWS ECR準備
- [ ] **ECRリポジトリ作成**
  - リポジトリ名: `learning-app-stg`
  - プライベートリポジトリ設定
  - ライフサイクルポリシー設定

- [ ] **AWS CLI設定確認**
  ```bash
  aws configure list
  aws ecr get-login-password --region ap-northeast-1
  ```

#### 4. Makefile作成・ECRプッシュ自動化
- [ ] **Makefile作成**
  - ECRプッシュの自動化
  - 環境変数での設定管理
  - エラーハンドリング

- [ ] **環境変数設定**
  ```bash
  export AWS_ACCOUNT_ID=123456789012
  export AWS_REGION=ap-northeast-1
  export ECR_REPOSITORY=learning-app-stg
  export IMAGE_TAG=latest
  ```

- [ ] **Makeコマンド実行**
  ```bash
  # ECRリポジトリ作成
  make ecr-create
  
  # イメージビルド・プッシュ
  make ecr-push
  
  # 全体実行
  make deploy
  ```

#### 5. 動作確認
- [ ] **ECRリポジトリ確認**
  - マネジメントコンソールでイメージ確認
  - イメージサイズ・タグ確認

- [ ] **セキュリティスキャン実行**
  - 脆弱性チェック結果確認

## プロジェクト構造

### Laravelプロジェクトのディレクトリ構造
```
learning-app/
├── app/                          # Laravelアプリケーション
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── vendor/
├── .env                          # 環境変数（ローカル用）
├── .env.example                  # 環境変数テンプレート
├── composer.json
├── composer.lock
├── artisan
├── Dockerfile                    # メインのDockerfile
├── docker-compose.yml            # ローカル開発用
├── docker-compose.prod.yml       # 本番用（オプション）
├── Makefile                      # ECRデプロイ自動化
├── ecr-lifecycle-policy.json     # ECRライフサイクルポリシー
└── docker/                       # Docker関連設定
    ├── nginx/                    # Nginx設定
    │   ├── default.conf          # Nginx設定ファイル
    │   └── nginx.conf            # メインNginx設定
    ├── php/                      # PHP設定
    │   ├── php.ini               # PHP設定
    │   └── php-fpm.conf          # PHP-FPM設定
    ├── mysql/                    # MySQL設定（ローカル用）
    │   ├── my.cnf                # MySQL設定
    │   └── init/                 # 初期化SQL
    │       └── 01_create_db.sql
    └── scripts/                  # 起動スクリプト
        ├── entrypoint.sh         # コンテナ起動スクリプト
        └── wait-for-it.sh        # サービス待機スクリプト
```

### Docker関連ファイルの詳細

#### docker/nginx/default.conf
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### docker/php/php.ini
```ini
[PHP]
post_max_size = 100M
upload_max_filesize = 100M
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000

[Date]
date.timezone = Asia/Tokyo

[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

#### docker/scripts/entrypoint.sh
```bash
#!/bin/bash
set -e

# Composer install
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Laravel setup
php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database migration (if needed)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

# Start PHP-FPM
exec "$@"
```

## 設定ファイル例

### Makefile
```makefile
# 環境変数（デフォルト値）
AWS_ACCOUNT_ID ?= $(shell aws sts get-caller-identity --query Account --output text)
AWS_REGION ?= ap-northeast-1
ECR_REPOSITORY ?= learning-app-stg
IMAGE_TAG ?= latest
LOCAL_IMAGE_NAME ?= learning-app

# ECRのフルURL
ECR_URI = $(AWS_ACCOUNT_ID).dkr.ecr.$(AWS_REGION).amazonaws.com/$(ECR_REPOSITORY)

.PHONY: help ecr-create ecr-login build tag push ecr-push deploy clean

help: ## ヘルプを表示
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

ecr-create: ## ECRリポジトリを作成
	@echo "Creating ECR repository: $(ECR_REPOSITORY)"
	aws ecr create-repository \
		--repository-name $(ECR_REPOSITORY) \
		--region $(AWS_REGION) \
		--image-scanning-configuration scanOnPush=true \
		--lifecycle-policy-text file://ecr-lifecycle-policy.json || true

ecr-login: ## ECRにログイン
	@echo "Logging in to ECR..."
	aws ecr get-login-password --region $(AWS_REGION) | docker login --username AWS --password-stdin $(AWS_ACCOUNT_ID).dkr.ecr.$(AWS_REGION).amazonaws.com

build: ## Dockerイメージをビルド
	@echo "Building Docker image: $(LOCAL_IMAGE_NAME):$(IMAGE_TAG)"
	docker build -t $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) .

tag: build ## イメージにECRタグを付与
	@echo "Tagging image for ECR: $(ECR_URI):$(IMAGE_TAG)"
	docker tag $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) $(ECR_URI):$(IMAGE_TAG)

push: ecr-login tag ## ECRにプッシュ
	@echo "Pushing to ECR: $(ECR_URI):$(IMAGE_TAG)"
	docker push $(ECR_URI):$(IMAGE_TAG)

ecr-push: push ## ECRプッシュのエイリアス

deploy: ecr-create push ## 全体デプロイ（ECR作成→ビルド→プッシュ）
	@echo "Deployment completed!"
	@echo "Image URI: $(ECR_URI):$(IMAGE_TAG)"

clean: ## ローカルイメージを削除
	@echo "Cleaning up local images..."
	docker rmi $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) || true
	docker rmi $(ECR_URI):$(IMAGE_TAG) || true

info: ## 現在の設定を表示
	@echo "AWS Account ID: $(AWS_ACCOUNT_ID)"
	@echo "AWS Region: $(AWS_REGION)"
	@echo "ECR Repository: $(ECR_REPOSITORY)"
	@echo "Image Tag: $(IMAGE_TAG)"
	@echo "ECR URI: $(ECR_URI):$(IMAGE_TAG)"
```

### ECRライフサイクルポリシー (ecr-lifecycle-policy.json)
```json
{
  "rules": [
    {
      "rulePriority": 1,
      "description": "Keep last 10 images",
      "selection": {
        "tagStatus": "tagged",
        "countType": "imageCountMoreThan",
        "countNumber": 10
      },
      "action": {
        "type": "expire"
      }
    },
    {
      "rulePriority": 2,
      "description": "Delete untagged images older than 1 day",
      "selection": {
        "tagStatus": "untagged",
        "countType": "sinceImagePushed",
        "countUnit": "days",
        "countNumber": 1
      },
      "action": {
        "type": "expire"
      }
    }
  ]
}
```

### .env.example (環境変数設定用)
```bash
# AWS設定
AWS_ACCOUNT_ID=123456789012
AWS_REGION=ap-northeast-1
ECR_REPOSITORY=learning-app-stg
IMAGE_TAG=latest

# Laravel設定
APP_NAME=LearningApp
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://learning-app-stg.example.com

# データベース設定（Secrets Managerから取得）
DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=learning_db
DB_USERNAME=
DB_PASSWORD=
```

### Dockerfile
```dockerfile
FROM php:8.2-fpm

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
```

### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: learning-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - learning-network

  webserver:
    image: nginx:alpine
    container_name: learning-nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - learning-network

networks:
  learning-network:
    driver: bridge
```

## トラブルシューティング

### よくあるエラー
1. **Make実行エラー**
   ```bash
   # 環境変数確認
   make info
   
   # AWS認証確認
   aws sts get-caller-identity
   ```

2. **ECRリポジトリ作成失敗**
   - IAM権限確認（ecr:CreateRepository）
   - リージョン設定確認
   - リポジトリ名の重複確認

3. **Docker build失敗**
   - Dockerfile構文確認
   - ベースイメージの存在確認
   - ネットワーク接続確認

4. **ECRプッシュ失敗**
   - ECRログイン状態確認
   - イメージサイズ制限確認
   - ネットワーク接続確認

### Makeコマンド使用例
```bash
# ヘルプ表示
make help

# 設定確認
make info

# ECRリポジトリ作成のみ
make ecr-create

# イメージビルドのみ
make build

# 全体実行（推奨）
make deploy

# クリーンアップ
make clean
```

## まとめ

### 作成したリソース
| リソース | 名前 | 用途 |
|---------|------|------|
| Laravelプロジェクト | learning-app | Webアプリケーション |
| ECRリポジトリ | learning-app-stg | Dockerイメージ保存 |
| Dockerイメージ | learning-app:latest | アプリケーション実行環境 |

### 次のステップ
- ECSサービス作成
- ALB設定
- Route53ドメイン設定
- SSL証明書設定

### セキュリティ考慮事項
- ECRリポジトリはプライベート設定
- イメージスキャン有効化
- 最小権限の原則でIAM設定
- 機密情報は環境変数で管理
