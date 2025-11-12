# Makefileを使ったデプロイ手順

## 概要

このプロジェクトでは、Makefileを使用してDockerイメージのビルド、ECRへのプッシュ、ECSへのデプロイを自動化しています。

## 前提条件

### 必要なツール

- **AWS CLI**: AWS認証情報が設定されていること
- **Docker**: Dockerイメージのビルドに必要
- **jq**: JSON処理用（macOS: `brew install jq`）
- **Make**: Makefileの実行に必要

### 環境変数

デフォルト値はMakefile内で設定されていますが、必要に応じて上書き可能です:

```bash
ENV=stg                    # 環境名（デフォルト: stg）
AWS_REGION=ap-northeast-1  # AWSリージョン（デフォルト: ap-northeast-1）
ECR_NAME=learning-app      # ECRリポジトリ名（デフォルト: learning-app）
GIT_COMMIT_HASH=<hash>     # Gitコミットハッシュ（自動取得）
```

## コマンド一覧

### ヘルプ表示

```bash
make help
```

すべての利用可能なコマンドとその説明を表示します。

### 設定情報の確認

```bash
make info ENV=stg
```

現在の設定（環境、AWSアカウントID、ECR URI、ECS設定など）を表示します。

## Dockerイメージ関連

### ECRにログイン

```bash
make docker-login ENV=stg
```

ECRにログインします。他のコマンドで自動的に実行されるため、通常は手動で実行する必要はありません。

### Dockerイメージをビルド

```bash
make build-image ENV=stg
```

Dockerイメージをビルドします。キャッシュを使用してビルドします。

### Dockerイメージをキャッシュなしでビルド

```bash
make build-image-no-cache ENV=stg
```

キャッシュを使用せずにDockerイメージをビルドします。依存関係の更新時などに使用します。

### ECRにプッシュ

```bash
make push-image ENV=stg
```

ビルド済みのDockerイメージをECRにプッシュします。`build-image` を先に実行する必要があります。

### イメージをリリース（ビルド＋プッシュ）

```bash
make release-image ENV=stg
```

以下の処理を順番に実行します:
1. ECRにログイン
2. Dockerイメージをビルド
3. ECRにプッシュ

### キャッシュなしでイメージをリリース

```bash
make release-image-no-cache ENV=stg
```

キャッシュを使用せずにイメージをビルドしてECRにプッシュします。

## ECSデプロイ関連

### ECSサービスを更新

```bash
make deploy-ecs ENV=stg
```

既にECRにプッシュ済みのイメージを使用してECSサービスを更新します。

**注意**: このコマンドを実行する前に、イメージをECRにプッシュしておく必要があります。

### 完全デプロイ（ビルド→プッシュ→ECS更新）

```bash
make deploy-full ENV=stg
```

以下の処理を順番に実行します:
1. Dockerイメージをビルド
2. ECRにプッシュ
3. ECSサービスを更新

**推奨**: 通常のデプロイではこのコマンドを使用します。

### 完全デプロイ＋マイグレーション実行

```bash
make deploy-full-with-migrate ENV=stg
```

以下の処理を順番に実行します:
1. Dockerイメージをビルド
2. ECRにプッシュ
3. ECSサービスを更新
4. データベースマイグレーションを実行

**推奨**: 初回デプロイ時や、データベーススキーマの変更がある場合に使用します。

### ECSサービスの状態確認

```bash
make ecs-status ENV=stg
```

ECSサービスの状態（ステータス、実行中のタスク数、希望タスク数、タスク定義）を表示します。

## データベース関連

### データベースマイグレーションを実行

```bash
make migrate ENV=stg
```

実行中のECSタスクに接続して、データベースマイグレーションを実行します。

**前提条件**:
- ECS Execが有効になっていること
- 実行中のタスクが存在すること

**注意**: マイグレーションは1つのタスクで実行すれば十分です（データベースは共有されています）。

### RDSのステータス確認

```bash
make db-status
```

RDSインスタンスのステータスを表示します。

## クリーンアップ

### ローカルイメージを削除

```bash
make clean ENV=stg
```

ローカルのDockerイメージを削除します。

## よくある使用パターン

### 初回デプロイ

```bash
# 1. 設定を確認
make info ENV=stg

# 2. 完全デプロイ＋マイグレーション
make deploy-full-with-migrate ENV=stg

# 3. デプロイ状態を確認
make ecs-status ENV=stg
```

### 通常のデプロイ（コード変更のみ）

```bash
# 完全デプロイ
make deploy-full ENV=stg

# デプロイ状態を確認
make ecs-status ENV=stg
```

### データベーススキーマ変更がある場合

```bash
# 1. デプロイ
make deploy-full ENV=stg

# 2. マイグレーション実行
make migrate ENV=stg
```

または、一度に実行:

```bash
make deploy-full-with-migrate ENV=stg
```

### 依存関係の更新時

```bash
# キャッシュなしでビルド
make deploy-no-cache ENV=stg
```

## トラブルシューティング

### jqが見つからないエラー

```bash
# macOSの場合
brew install jq

# Linuxの場合
sudo apt-get install jq
```

### ECS Execが有効になっていない

マイグレーションコマンドを実行するには、ECSサービスでECS Execが有効になっている必要があります。

- ECSコンソール → サービス → `learning-ecs-service-stg` → 「更新」
- 「デプロイメント設定」セクションで「Enable ECS Exec」を有効化

### タスクが見つからないエラー

マイグレーション実行時に「No running tasks found」エラーが出る場合:

1. デプロイが完了してタスクが起動するまで待機
2. `make ecs-status ENV=stg` でタスクが実行中か確認
3. 再度マイグレーションを実行

### デプロイが失敗する場合

1. AWS認証情報が正しく設定されているか確認
   ```bash
   aws sts get-caller-identity
   ```

2. ECRリポジトリが存在するか確認
   ```bash
   aws ecr describe-repositories --repository-names learning-app-stg
   ```

3. ECSクラスターとサービスが存在するか確認
   ```bash
   aws ecs describe-services --cluster learning-cluster-stg --services learning-ecs-service-stg
   ```

## GitHub Actionsでの使用

GitHub Actionsでも同じMakefileコマンドを使用できます:

```yaml
# .github/workflows/deploy.yml
- name: Deploy to ECS
  env:
    AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
    AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS }}
    AWS_DEFAULT_REGION: ap-northeast-1
  run: make deploy-full-with-migrate ENV=stg
```

## まとめ

| コマンド | 説明 | 使用タイミング |
|---------|------|---------------|
| `make info` | 設定情報を表示 | デプロイ前の確認 |
| `make deploy-full` | 完全デプロイ | 通常のデプロイ |
| `make deploy-full-with-migrate` | デプロイ＋マイグレーション | 初回デプロイ、スキーマ変更時 |
| `make migrate` | マイグレーションのみ | デプロイ後のマイグレーション |
| `make ecs-status` | ECS状態確認 | デプロイ後の確認 |
| `make deploy-no-cache` | キャッシュなしデプロイ | 依存関係更新時 |

## 参考リンク

- [ECSタスク定義ドキュメント](../infrastructure-setup/6_ecs-task-definition.md)
- [ECSサービス設定ドキュメント](../infrastructure-setup/7_ecs-service.md)
- [ECSタスクへの接続とマイグレーション実行手順](./ecs-task-connection-and-migration.md)

