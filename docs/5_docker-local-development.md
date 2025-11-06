# Docker環境でのローカル開発手順

このドキュメントでは、Docker環境でLaravelアプリケーションをローカルで起動する手順を説明します。

## 前提条件

- Docker Desktopがインストールされていること
- プロジェクトのルートディレクトリにいること

## 手順

### 1. 環境変数ファイルの準備

`.env`ファイルが存在しない場合は作成：

```bash
# .env.exampleがあればコピー
cp .env.example .env

# または、最低限の設定で作成（PostgreSQL使用）
cat > .env << EOF
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

# ローカル開発環境（Docker Compose使用時）
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres
EOF
```

**注意**: AWS環境（RDS使用時）では、`.env`ファイルまたは環境変数で以下のように設定してください：

```bash
DB_CONNECTION=pgsql
DB_HOST=your-rds-endpoint.ap-northeast-1.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 2. Dockerコンテナのビルドと起動

```bash
# コンテナをビルドして起動
docker-compose up -d --build

# ログを確認
docker-compose logs -f
```

### 3. Laravelの初期設定（コンテナ内で実行）

```bash
# appコンテナに入る
docker-compose exec app bash

# アプリケーションキー生成（entrypoint.shで自動実行されるが、念のため）
php artisan key:generate

# マイグレーション実行（PostgreSQLを使用する場合）
php artisan migrate

# コンテナから出る
exit
```

または、コンテナ外から実行：

```bash
# マイグレーション実行
docker-compose exec app php artisan migrate

# ストレージのシンボリックリンク作成（必要に応じて）
docker-compose exec app php artisan storage:link
```

### 4. ブラウザでアクセス

```
http://localhost:8080
```

## 動作確認コマンド

### コンテナの状態確認

```bash
docker-compose ps
```

### ログ確認

```bash
# アプリケーションコンテナのログ
docker-compose logs app

# Webサーバーコンテナのログ
docker-compose logs webserver

# 全コンテナのログをリアルタイムで確認
docker-compose logs -f
```

### コンテナの停止

```bash
docker-compose down
```

### コンテナの再起動

```bash
docker-compose restart
```

## 注意点

### 1. entrypoint.shの動作

- コンテナ起動時に自動で`key:generate`が実行されます
- `.env`に`APP_KEY`が空の場合は自動生成されます
- `config:cache`、`route:cache`、`view:cache`も自動実行されます

### 2. データベース

- **ローカル開発環境**: PostgreSQLコンテナが自動的に起動します（`docker-compose.yml`で定義済み）
- **AWS環境**: RDS（PostgreSQL）を使用します。環境変数でRDSの接続情報を設定してください
- データベースの接続設定は`.env`ファイルの`DB_*`環境変数で制御されます
- ローカル環境では`DB_HOST=postgres`（コンテナ名）を設定します
- AWS環境では`DB_HOST`にRDSのエンドポイントを設定します

### 3. ストレージ権限

- `storage`と`bootstrap/cache`の権限はDockerfileで設定済みです
- 問題があれば、以下のコマンドで権限を再設定できます：
  ```bash
  docker-compose exec app chmod -R 775 storage bootstrap/cache
  ```

### 4. ボリュームマウント

- `docker-compose.yml`でローカルのファイルがコンテナにマウントされています
- ファイルを編集すると、コンテナ内にも反映されます（再起動不要）

## トラブルシューティング

### 500エラーが出る場合

```bash
# 設定キャッシュをクリア
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### コンテナが起動しない場合

```bash
# コンテナを停止して削除
docker-compose down

# イメージを再ビルドして起動
docker-compose up -d --build
```

### ポートが既に使用されている場合

`docker-compose.yml`の`ports`設定を変更：

```yaml
ports:
  - "8081:80"  # 8080から8081に変更
```

### データベース接続エラーが出る場合

- PostgreSQLコンテナが起動しているか確認：
  ```bash
  docker-compose ps
  ```
- コンテナのログを確認：
  ```bash
  docker-compose logs postgres
  ```
- `.env`ファイルのDB設定を確認：
  ```bash
  # ローカル環境の場合
  DB_HOST=postgres
  DB_PORT=5432
  DB_DATABASE=laravel
  DB_USERNAME=postgres
  DB_PASSWORD=postgres
  ```
- データベースを再作成する場合：
  ```bash
  # コンテナとボリュームを削除
  docker-compose down -v
  
  # 再起動
  docker-compose up -d
  
  # マイグレーション実行
  docker-compose exec app php artisan migrate
  ```

### Composer依存関係のエラー

```bash
# コンテナ内でComposerを再インストール
docker-compose exec app composer install
```

## 開発時の便利なコマンド

### Artisanコマンドの実行

```bash
# マイグレーション
docker-compose exec app php artisan migrate

# マイグレーションロールバック
docker-compose exec app php artisan migrate:rollback

# シーダー実行
docker-compose exec app php artisan db:seed

# ルート一覧表示
docker-compose exec app php artisan route:list

# Tinker起動
docker-compose exec app php artisan tinker
```

### コンテナ内でシェルを起動

```bash
# appコンテナに入る
docker-compose exec app bash

# rootユーザーで入る
docker-compose exec -u root app bash
```

### ログファイルの確認

```bash
# Laravelのログを確認
docker-compose exec app tail -f storage/logs/laravel.log
```

## 関連ドキュメント

- [ECRデプロイメント手順](./4_laravel-ecr-deployment.md)

