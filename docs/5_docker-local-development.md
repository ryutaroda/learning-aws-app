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

# または、最低限の設定で作成
cat > .env << EOF
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
EOF
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

# マイグレーション実行（SQLiteを使用する場合）
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

- 現在はSQLiteを使用する設定です（`database/database.sqlite`）
- PostgreSQLを使用する場合は、`docker-compose.yml`にDBサービスを追加し、`.env`を更新してください

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

- SQLiteファイルが存在するか確認：
  ```bash
  ls -la database/database.sqlite
  ```
- 存在しない場合は作成：
  ```bash
  touch database/database.sqlite
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

