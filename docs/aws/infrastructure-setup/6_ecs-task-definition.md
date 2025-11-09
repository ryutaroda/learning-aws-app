# ECSタスク定義設定ドキュメント

## 作成順序

### 前提条件

1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`
3. ✅ **環境変数設定完了**: S3バケット・Secrets Manager
4. ✅ **ECRイメージ作成完了**: `learning-app-stg`（統合Dockerfileでビルド）
5. ✅ **ECSクラスター作成完了**: `learning-cluster-stg`
6. ✅ **IAMロール作成完了**: タスクロール・タスク実行ロール
7. ✅ **セキュリティグループ作成完了**: ECSタスク用・ALB用

### 作成手順

#### 1. CloudWatch Logs設定

- [X] **ロググループ作成**
  - ロググループ名: `/ecs/learning-app-stg`
  - 保持期間: 7日間（学習環境のため）

#### 2. タスク定義の基本設定

- [X] **タスク定義作成**
  - タスク定義ファミリー名: `learning-app-task-stg`
  - 起動タイプ: Fargate
  - タスクロール: `learning-ecs-task-role-stg`
  - タスク実行ロール: `learning-ecs-task-execution-role-stg`
  - ネットワークモード: awsvpc（Fargateの場合は自動設定）
  - タスクサイズ:
    - CPU: 0.5 vCPU (512) または 1 vCPU (1024)
    - メモリ: 1 GB (1024) または 2 GB (2048)

#### 3. コンテナ定義（1コンテナ構成 - 推奨）

**重要**: ECSでは同じタスク内のコンテナ間でファイルシステムを共有できないため、NginxとPHP-FPMを1つのコンテナに統合する構成を推奨します。

- [X] **基本設定**

  - コンテナ名: `app`
  - イメージURI: `{AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-1.amazonaws.com/learning-app-stg:latest`
  - メモリ制限: 1024 MiB（ソフト制限）
  - 必須: はい（Essential）
  - 作業ディレクトリ: `/var/www` <- ??
- [X] **ポートマッピング**

  - コンテナポート: 80
  - プロトコル: tcp
- [X] **環境変数設定**

  - 直接設定する場合:
    ```
    APP_NAME: LearningApp
    APP_ENV: staging
    APP_KEY=base64:jK4MA/S46crir/ ~ 
    APP_DEBUG: false
    APP_URL: https://your-domain.com（ALBのURLまたは独自ドメイン）
    DB_CONNECTION: pgsql
    DB_PORT: 5432
    DB_DATABASE: kanaoka
    LOG_CHANNEL: stderr
    LOG_LEVEL: info
    SESSION_DRIVER: database
    CACHE_DRIVER: database
    QUEUE_CONNECTION: database
    ```
  - Secrets Managerから取得（推奨）:
    ```
    DB_HOST:
      valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:host::
    DB_USERNAME:
      valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:operator_user::
    DB_PASSWORD:
      valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:operator_password::
    ```
- [X] **ログ設定**

  - ログドライバー: awslogs
  - ログオプション:
    - awslogs-group: `/ecs/learning-app-stg`
    - awslogs-region: `ap-northeast-1`
    - awslogs-stream-prefix: `app`
    - awslogs-create-group: true
- [X] **ヘルスチェック**

  - コマンド: `CMD-SHELL,wget --no-verbose --tries=1 --spider http://localhost/up || exit 1`
  - 間隔: 30秒
  - タイムアウト: 5秒
  - 再試行回数: 3
  - 開始期間: 60秒

#### 4. 動作確認

- [ ] **タスク定義確認**
  - マネジメントコンソールでタスク定義確認
  - コンテナ設定・環境変数確認
- [ ] **タスク実行テスト**
  - タスクを手動実行して動作確認
  - ログ出力確認

---

## 基本設定

**タスク定義ファミリー名**: `learning-app-task-stg`
**起動タイプ**: Fargate
**ネットワークモード**: awsvpc

### タスクロール

**ロール名**: `learning-ecs-task-role-stg`

- Secrets Managerへのアクセス権限
- CloudWatch Logsへのアクセス権限
- AmazonSSMManagedInstanceCore
- CloudWatchLogsFullAccess

### タスク実行ロール

**ロール名**: `learning-ecs-task-execution-role-stg`

- ECRからイメージプル権限
- CloudWatch Logsへの書き込み権限
- Secrets Managerからシークレット取得権限
- AmazonECSTaskExecutionRolePolicy
- AmazonS3ReadOnlyAccess
- secrets-manager-readonly-stg

### タスクサイズ

**CPU**: 0.5 vCPU (512) または 1 vCPU (1024)
**メモリ**: 1 GB (1024) または 2 GB (2048)

---

## コンテナ設定詳細（1コンテナ構成）

### コンテナ: learning-app（Nginx + PHP-FPM統合）

#### 基本設定

- **コンテナ名**: `learning-app`
- **イメージURI**: `{AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-1.amazonaws.com/learning-app-stg:latest`
- **メモリ制限**: 1024 MiB（ソフト制限）
- **必須**: はい（Essential）
- **作業ディレクトリ**: `/var/www`

#### ポートマッピング

- **コンテナポート**: 80
- **プロトコル**: tcp

#### 環境変数

**直接設定する場合:**

```
APP_NAME: LearningApp
APP_ENV: staging
APP_DEBUG: false
APP_URL: https://your-domain.com（ALBのURLまたは独自ドメイン）
DB_CONNECTION: pgsql
DB_HOST: （RDSエンドポイント）
DB_PORT: 5432
DB_DATABASE: kanaoka
LOG_CHANNEL: stderr
LOG_LEVEL: info
SESSION_DRIVER: database
CACHE_DRIVER: database
QUEUE_CONNECTION: database
```

**Secrets Managerからの環境変数（推奨）:**

```
DB_HOST:
　valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:host::

DB_USERNAME:
  valueFrom: arn:aws:secretsmanager:ap-northeast-1:{ACCOUNT_ID}:secret:db-main-instance-stg:operator_user::

DB_PASSWORD:
  valueFrom: arn:aws:secretsmanager:ap-northeast-1:{ACCOUNT_ID}:secret:db-main-instance-stg:operator_password::
```

#### ログ設定

- **ログドライバー**: awslogs
- **ログオプション**:
  - awslogs-group: `/ecs/learning-app-stg`
  - awslogs-region: `ap-northeast-1`
  - awslogs-stream-prefix: `app`
  - awslogs-create-group: true

#### ヘルスチェック

- **コマンド**: `CMD-SHELL,wget --no-verbose --tries=1 --spider http://localhost/up || exit 1`
- **間隔**: 30秒
- **タイムアウト**: 5秒
- **再試行回数**: 3
- **開始期間**: 60秒

---

## なぜ1コンテナ構成を推奨するか？

### ❌ 2コンテナ構成の問題点

**重大な問題: ファイルシステムの共有ができない**

ECSでは、同じタスク内のコンテナでもファイルシステムは分離されています。docker-composeのような`volumes`マウントはできません。

1. **Nginxが `/var/www/public` にアクセスできない**

   - Nginxは静的ファイル（CSS/JS/画像）にアクセスする必要がある
   - PHPファイルを直接配信しないが、`/var/www/public`へのアクセスが必要
2. **2コンテナ構成でファイル共有するには**:

   - **EFSを使用**: 複雑で運用コストが増える
   - **両方のイメージにLaravelコードをコピー**: 非効率でイメージサイズが2倍
3. **ローカル開発とECSで構成が異なる**:

   - ローカル: docker-compose + volumes で共有
   - ECS: ファイル共有ができない

### ✅ 1コンテナ構成のメリット

- ✅ ファイルシステムの共有問題が完全に解決
- ✅ EFSが不要（シンプル）
- ✅ 設定が簡単
- ✅ ヘルスチェックが正常に動作
- ✅ イメージが1つだけ
- ✅ デプロイが簡単
- ✅ 本番運用実績が豊富

### デメリット

- PHP-FPMとNginxを個別にスケールできない（実際にはほとんど問題にならない）
- コンテナサイズがやや大きくなる（数十MB程度）

---

## Dockerfile（ECS用 - 統合版）

### 統合Dockerfile

```dockerfile
FROM php:8.4-fpm

# NginxとSupervisorをインストール
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    wget \
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

# Nginx設定ファイルコピー（ECS用 - localhost:9000）
COPY docker/nginx/default.conf.ecs /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Supervisor設定ファイルコピー
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

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

# Nginxのログを標準出力にリダイレクト
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### 必要なファイル構成

```
docker/
├── nginx/
│   ├── default.conf         # ローカル開発用（app:9000）
│   └── default.conf.ecs     # ECS用（localhost:9000）
├── php/
│   └── php.ini
├── supervisor/
│   └── supervisord.conf     # 新規作成
└── scripts/
    └── entrypoint.sh
```

### Nginx設定ファイル（ECS用）

**`docker/nginx/default.conf.ecs`**

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
        # 同じコンテナ内なのでlocalhost:9000
        fastcgi_pass localhost:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Supervisor設定ファイル

**`docker/supervisor/supervisord.conf`**

```ini
[supervisord]
nodaemon=true
user=root
logfile=/dev/null
logfile_maxbytes=0
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=/usr/local/sbin/php-fpm --nodaemonize
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
priority=5

[program:nginx]
command=/usr/sbin/nginx -g 'daemon off;'
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
priority=10
```

---

## ローカル開発環境との違い

### ローカル開発環境

- **構成**: docker-compose.yml + 2コンテナ（app + nginx）
- **ファイル共有**: volumesで共有
- **Nginx設定**: `docker/nginx/default.conf`（`app:9000`を使用）

### ECS環境

- **構成**: 1コンテナ（Nginx + PHP-FPM統合）
- **ファイル共有**: 不要（同じコンテナ内）
- **Nginx設定**: `docker/nginx/default.conf.ecs`（`localhost:9000`を使用）

**推奨**: ECS用のDockerfileを`Dockerfile.ecs`として作成し、ローカル開発用とは分離する

---

## Secrets Managerの設定例

**シークレット名:** `db-main-instance-stg`

```json
{
  "host": "learning-db-stg.xxxxxxxxxx.ap-northeast-1.rds.amazonaws.com",
  "operator_user": "postgres",
  "operator_password": "password"
}
```

---

## トラブルシューティング

### よくある問題

1. **ヘルスチェックが失敗する**

   - `wget`がインストールされているか確認（Dockerfileに含まれているか）
   - `/up`エンドポイントが正しく動作しているか確認
   - ログを確認してエラー内容を特定
2. **NginxとPHP-FPMが起動しない**

   - Supervisorの設定が正しいか確認
   - ログを確認してエラー内容を特定
   - ポート80が正しく公開されているか確認
3. **環境変数が取得できない**

   - Secrets ManagerのARNが正しいか確認
   - タスク実行ロールにSecrets Managerへのアクセス権限があるか確認
   - シークレット名とキー名が正しいか確認
4. **ログが出力されない**

   - CloudWatch Logsグループが作成されているか確認
   - タスク実行ロールにCloudWatch Logsへの書き込み権限があるか確認
   - ログドライバーの設定が正しいか確認
   - NginxとPHP-FPMのログが標準出力にリダイレクトされているか確認
5. **静的ファイルが表示されない**

   - `/var/www/public`へのアクセス権限を確認
   - Nginx設定の`root`ディレクトリが正しいか確認

---

## 参考リンク

- [AWS ECS タスク定義パラメータ](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/task_definition_parameters.html)
- [AWS Fargate タスク定義](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/task_definitions.html)
- [ECS コンテナ間通信](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/task-networking.html)
