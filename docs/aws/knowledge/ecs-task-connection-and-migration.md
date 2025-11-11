# ECSタスクへの接続とマイグレーション実行手順

## 概要

ECSタスク（Fargate）に接続して、データベースマイグレーションなどの管理作業を実行する方法を説明します。

## 前提条件

1. **ECS Execが有効になっていること**
   - ECSサービス作成時に「Enable ECS Exec」を有効にする必要があります
   - 有効になっていない場合は、サービスを更新して有効化してください

2. **タスク実行ロールにSSM権限があること**
   - `AmazonSSMManagedInstanceCore` ポリシーが付与されているか確認

## ECSタスクへの接続方法

### 方法1: AWSコンソールから接続（推奨）

1. **ECSコンソールを開く**
   - ECS → クラスター → `learning-cluster-stg`
   - サービス → `learning-ecs-service-stg` を選択

2. **実行中のタスクを選択**
   - 「タスク」タブを開く
   - 実行中のタスクを選択

3. **ECS Execで接続**
   - 「接続」タブをクリック
   - 「ECS Exec」セクションで「接続」をクリック
   - ブラウザでターミナルが開きます

### 方法2: AWS CLIから接続

#### 1. タスクIDの取得

```bash
# リージョンを指定してタスク一覧を取得
aws ecs list-tasks \
  --cluster learning-cluster-stg \
  --service-name learning-ecs-service-stg \
  --region ap-northeast-1

# 出力例:
# {
#   "taskArns": [
#     "arn:aws:ecs:ap-northeast-1:{AWS_ACCOUNT_ID}:task/learning-cluster-stg/{TASK_ID}"
#   ]
# }
```

#### 2. ECS Execで接続

```bash
# タスクIDを抽出（ARNから最後の部分を取得）
# 例: 85b7968ceca5479ba40728bfe5c29d95

aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task <タスクID> \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1
```

**リージョンの指定方法:**

```bash
# 方法1: コマンドに --region を追加（推奨）
aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task <タスクID> \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1

# 方法2: 環境変数で設定
export AWS_DEFAULT_REGION=ap-northeast-1
aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task <タスクID> \
  --container app \
  --interactive \
  --command "/bin/bash"

# 方法3: AWS設定ファイルで設定
aws configure
# リージョンを入力: ap-northeast-1
```

## 接続後の確認コマンド

ECSタスクに接続できたら、以下を実行して状態を確認できます:

```bash
# PHP-FPMプロセスの確認
ps aux | grep php-fpm

# Nginxプロセスの確認
ps aux | grep nginx

# ポート9000でリッスンしているか確認（netstatが使えない場合は別の方法で確認）
# Supervisorの状態確認
supervisorctl status

# ヘルスチェックエンドポイントの確認
curl http://localhost/up

# ルートパスの確認
curl http://localhost/
```

## データベースマイグレーションの実行

### マイグレーション実行手順

1. **ECSタスクに接続**
   - 上記の方法でECSタスクに接続

2. **マイグレーションを実行**
   ```bash
   # 作業ディレクトリに移動
   cd /var/www
   
   # マイグレーションを実行
   php artisan migrate --force
   ```
   
   **注意**: `--force` オプションは本番環境で実行する場合に必要です。

3. **マイグレーション結果の確認**
   ```bash
   # マイグレーション状態を確認
   php artisan migrate:status
   ```

### セッションテーブルの作成

Laravelでセッション管理にデータベースを使用する場合、セッションテーブルが必要です:

```bash
# セッションテーブル用のマイグレーションファイルを作成
php artisan session:table

# マイグレーションを実行
php artisan migrate --force
```

### 環境変数の確認

マイグレーション実行前に、データベース接続情報を確認:

```bash
# データベース接続情報を確認
echo $DB_HOST
echo $DB_DATABASE
echo $DB_USERNAME

# セッションドライバーの確認
echo $SESSION_DRIVER
# database になっていることを確認
```

### データベース接続テスト

```bash
# Laravel Tinkerでデータベース接続をテスト
php artisan tinker
# その後、以下を実行
DB::connection()->getPdo();

# または、直接PostgreSQLに接続（psqlがインストールされている場合）
psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "SELECT 1;"
```

## トラブルシューティング

### ECS Execが表示されない場合

1. **タスク実行ロールの確認**
   - IAMコンソール → ロール → `learning-ecs-task-execution-role-stg`
   - `AmazonSSMManagedInstanceCore` ポリシーが付与されているか確認

2. **サービスでECS Execが有効になっているか確認**
   - ECSコンソール → サービス → `learning-ecs-service-stg`
   - 「デプロイメント設定」セクションで「Enable ECS Exec」が有効か確認
   - 有効になっていない場合は、サービスを更新して有効化

### 接続できない場合

1. **タスクの状態確認**
   - タスクが「実行中」状態か確認
   - タスクが停止している場合は接続できません

2. **セキュリティグループの確認**
   - セキュリティグループでSSMポート（443）が許可されているか確認
   - 通常はFargateで自動的に設定されます

### マイグレーションエラーの場合

1. **データベース接続エラー**
   ```
   SQLSTATE[08006] [7] connection to server at "..." failed: timeout expired
   ```
   - RDSのセキュリティグループでECSタスクからの接続が許可されているか確認
   - セキュリティグループIDで指定する必要があります

2. **テーブルが存在しないエラー**
   ```
   SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "sessions" does not exist
   ```
   - マイグレーションを実行してテーブルを作成
   - セッションテーブルの場合は `php artisan session:table` を実行

## よくある作業フロー

### 初回デプロイ後のマイグレーション

```bash
# 1. ECSタスクに接続
aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task <タスクID> \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1

# 2. 作業ディレクトリに移動
cd /var/www

# 3. 環境変数を確認
echo $DB_HOST
echo $SESSION_DRIVER

# 4. マイグレーションを実行
php artisan migrate --force

# 5. セッションテーブルが必要な場合
php artisan session:table
php artisan migrate --force

# 6. 動作確認
curl http://localhost/up
```

### アプリケーションの状態確認

```bash
# 1. ECSタスクに接続（上記参照）

# 2. プロセス確認
ps aux | grep php-fpm
ps aux | grep nginx

# 3. ログ確認
tail -f /var/www/storage/logs/laravel.log

# 4. データベース接続テスト
php artisan tinker
DB::connection()->getPdo();
```

## 注意事項

### マイグレーションの実行タイミング

- **初回デプロイ時**: ECSタスクに接続して手動で実行
- **自動化を検討**: ECSタスク定義のentrypointスクリプトで自動実行する方法もあります
- **本番環境**: `--force` オプションが必要です

### セキュリティ

- ECS ExecはSSM経由で接続するため、セキュアです
- 接続ログはCloudWatch Logsに記録されます
- 不要な権限は付与しないように注意してください

## まとめ

| 作業 | コマンド | 説明 |
|-----|---------|------|
| **タスクID取得** | `aws ecs list-tasks` | 実行中のタスクIDを取得 |
| **ECS Exec接続** | `aws ecs execute-command` | タスクに接続 |
| **マイグレーション** | `php artisan migrate --force` | データベースマイグレーション実行 |
| **セッションテーブル** | `php artisan session:table` | セッションテーブル作成 |
| **状態確認** | `php artisan migrate:status` | マイグレーション状態確認 |

## 参考リンク

- [ECSタスク定義ドキュメント](../infrastructure-setup/6_ecs-task-definition.md)
- [ECSサービス設定ドキュメント](../infrastructure-setup/7_ecs-service.md)
- [AWS ECS Exec ドキュメント](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/ecs-exec.html)

