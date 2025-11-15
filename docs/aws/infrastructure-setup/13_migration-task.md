# マイグレーション専用タスク定義設定ドキュメント

## 概要

データベースマイグレーションを実行するための専用ECSタスク定義を作成します。アプリケーションタスクとは分離することで、マイグレーション失敗時でもアプリケーションが起動できるようにします。

## 作成順序

### 前提条件

1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`
3. ✅ **環境変数設定完了**: S3バケット・Secrets Manager
4. ✅ **ECRイメージ作成完了**: `learning-app-stg`
5. ✅ **ECSクラスター作成完了**: `learning-cluster-stg`
6. ✅ **IAMロール作成完了**: タスクロール・タスク実行ロール（アプリケーションタスクと同じものを使用）
7. ✅ **セキュリティグループ作成完了**: ECSタスク用（アプリケーションタスクと同じものを使用）
8. ✅ **アプリケーションタスク定義作成完了**: `learning-app-task-stg`

### 作成手順

#### 1. タスク定義の基本設定

- [x] **タスク定義作成**
  - タスク定義ファミリー名: `learning-db-migrate-task-stg`
  - 起動タイプ: Fargate
  - タスクロール: `learning-ecs-task-role-stg`（アプリケーションタスクと同じ）
  - タスク実行ロール: `learning-ecs-task-execution-role-stg`（アプリケーションタスクと同じ）
  - ネットワークモード: awsvpc（Fargateの場合は自動設定）
  - タスクサイズ:
    - CPU: 0.25 vCPU (256)（マイグレーションは軽量なため）
    - メモリ: 512 MB (512)（マイグレーションは軽量なため）

#### 2. コンテナ定義

- [x] **基本設定**
  - コンテナ名: `migrate`
  - イメージURI: `{AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-1.amazonaws.com/learning-app-stg:latest`（アプリケーションと同じイメージ）
  - メモリ制限（Memory soft limit）: 512 MiB（ソフト制限）
  - 必須: はい（Essential）
  - Port mappings: 不要

- [x] **環境変数設定**
  - アプリケーションタスク定義と同じ環境変数を設定
  - Secrets Managerから取得する設定も同じ

- [x] **コマンド設定**
  - エントリーポイント: sh,-c
  - コマンド: `php artisan migrate --force`
  - 作業ディレクトリ: `/var/www`

- [x] **ログ設定**
  - ログドライバー: awslogs(CloudWatch)
  - ログオプション:
    - awslogs-group: `/ecs/learning-db-migrate-task-stg`
    - awslogs-region: `ap-northeast-1`
    - awslogs-stream-prefix: `migrate`
    - awslogs-create-group: true

- [x] **ヘルスチェック**
  - ヘルスチェックは不要（ワンショットタスクのため）

## 基本設定

**タスク定義ファミリー名**: `learning-db-migrate-task-stg`
**起動タイプ**: Fargate
**ネットワークモード**: awsvpc

### タスクロール

**ロール名**: `learning-ecs-task-role-stg`（アプリケーションタスクと同じ）

- Secrets Managerへのアクセス権限
- CloudWatch Logsへのアクセス権限
- AmazonSSMManagedInstanceCore
- CloudWatchLogsFullAccess

### タスク実行ロール

**ロール名**: `learning-ecs-task-execution-role-stg`（アプリケーションタスクと同じ）

- ECRからイメージプル権限
- CloudWatch Logsへの書き込み権限
- Secrets Managerからシークレット取得権限
- AmazonECSTaskExecutionRolePolicy
- AmazonS3ReadOnlyAccess
- secrets-manager-readonly-stg

### タスクサイズ

**CPU**: 0.25 vCPU (256)（マイグレーションは軽量なため最小構成）
**メモリ**: 512 MB (512)（マイグレーションは軽量なため最小構成）

## コンテナ設定詳細

### コンテナ: migrate（マイグレーション専用）

#### 基本設定

- **コンテナ名**: `migrate`
- **イメージURI**: `{AWS_ACCOUNT_ID}.dkr.ecr.ap-northeast-1.amazonaws.com/learning-app-stg:latest`
- **メモリ制限**: 512 MiB（ソフト制限）
- **必須**: はい（Essential）
- **作業ディレクトリ**: `/var/www`

#### コマンド設定

- **エントリーポイント**: （空欄）
- **コマンド**: `php artisan migrate --force`

#### 環境変数

アプリケーションタスク定義と同じ環境変数を設定します。

**Secrets Managerからの環境変数（推奨）:**

```
DB_HOST:
  valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:host::

DB_USERNAME:
  valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:operator_user::

DB_PASSWORD:
  valueFrom: arn:aws:secretsmanager:ap-northeast-1:{AWS_ACCOUNT_ID}:secret:db-main-instance-stg:operator_password::
```

その他の環境変数もアプリケーションタスク定義と同じものを設定してください。

#### ログ設定

- **ログドライバー**: awslogs
- **ログオプション**:
  - awslogs-group: `/ecs/learning-app-migrate-stg`
  - awslogs-region: `ap-northeast-1`
  - awslogs-stream-prefix: `migrate`
  - awslogs-create-group: true

#### ヘルスチェック

- ヘルスチェックは不要（ワンショットタスクのため）

## ネットワーク設定

### VPC設定

**VPC**: `learning-stg` (10.0.0.0/16)

### サブネット

マイグレーションタスクを実行する際に指定するサブネット:
- `private-subnet-nat-1a-stg` (10.0.16.0/20) - ap-northeast-1a
- `private-subnet-nat-1c-stg` (10.0.80.0/20) - ap-northeast-1c

**注意**: アプリケーションタスクと同じプライベート（NAT）サブネットを使用します。

### セキュリティグループ

**セキュリティグループ**: `learning-app-sg-stg`（アプリケーションタスクと同じ）

- RDSへの接続が許可されている必要があります

### パブリックIP

**パブリックIPの自動割り当て**: **無効（DISABLED）**
- プライベートサブネットではパブリックIPは不要
- アウトバウンド通信はNATゲートウェイ経由
- データベース接続のみ必要

## 動作確認

- [ ] **タスク定義確認**
  - マネジメントコンソールでタスク定義確認
  - コンテナ設定・環境変数確認

- [ ] **タスク実行テスト**
  - ECSコンソールから手動でタスクを実行
  - マイグレーションが正常に完了するか確認
  - CloudWatch Logsでログを確認

## Makefileでの実行

マイグレーション専用タスクを実行するMakefileコマンドが用意されています:

```bash
# マイグレーション専用タスクを実行
make migrate-task ENV=stg

# デプロイ＋マイグレーション専用タスク実行
make deploy-full-with-migrate-task ENV=stg
```

詳細は [Makefileデプロイ手順ドキュメント](../knowledge/deployment-with-makefile.md) を参照してください。

## メリット

### アプリケーションタスクと分離する利点

1. **マイグレーション失敗時でもアプリが起動**
   - マイグレーションが失敗しても、アプリケーションタスクは正常に起動
   - ロールバックが容易

2. **リソースの最適化**
   - マイグレーションは軽量なため、最小構成で実行可能
   - アプリケーションタスクのリソースを消費しない

3. **ログの分離**
   - マイグレーション専用のロググループで管理
   - デバッグが容易

4. **実行タイミングの制御**
   - デプロイ後に任意のタイミングで実行可能
   - CI/CDパイプラインに組み込みやすい

## 注意事項

### マイグレーション実行時の注意点

1. **データベースロック**
   - マイグレーション実行中はデータベースにロックがかかる場合がある
   - 本番環境ではメンテナンス時間帯に実行することを推奨

2. **バックアップ**
   - マイグレーション実行前にデータベースのバックアップを取得することを推奨

3. **ロールバック計画**
   - マイグレーション失敗時のロールバック手順を事前に準備

4. **実行タイミング**
   - デプロイ後に実行することを推奨
   - アプリケーションタスクが起動してから実行

## トラブルシューティング

### マイグレーションタスクが失敗する場合

1. **CloudWatch Logsを確認**
   - `/ecs/learning-db-migrate-stg` ロググループを確認
   - エラーメッセージを確認

2. **データベース接続確認**
   - RDSのセキュリティグループでECSタスクからの接続が許可されているか確認
   - データベース認証情報が正しいか確認

3. **環境変数確認**
   - Secrets Managerから環境変数が正しく取得できているか確認
   - タスク定義の環境変数設定を確認

### タスクが起動しない場合

1. **サブネットの確認**
   - 指定したサブネットが存在するか確認
   - サブネットが正しいVPCに属しているか確認

2. **セキュリティグループの確認**
   - セキュリティグループが存在するか確認
   - RDSへの接続が許可されているか確認

3. **IAMロールの確認**
   - タスクロールとタスク実行ロールが正しく設定されているか確認

## まとめ

### 作成したリソース

| リソース | 名前 | 用途 |
|---------|------|------|
| CloudWatch Logs | `/ecs/learning-db-migrate-stg` | マイグレーションログ |
| タスク定義 | `learning-db-migrate-task-stg` | マイグレーション専用タスク |

### 重要なポイント

- **アプリケーションタスクとは分離**: マイグレーション失敗時でもアプリが起動
- **最小構成**: CPU 0.25 vCPU、メモリ 512 MBで十分
- **同じイメージを使用**: アプリケーションと同じECRイメージを使用
- **同じ環境変数**: アプリケーションタスクと同じ環境変数を設定

## 参考リンク

- [ECSタスク定義ドキュメント](./6_ecs-task-definition.md)
- [ECSサービス設定ドキュメント](./7_ecs-service.md)
- [Makefileデプロイ手順ドキュメント](../knowledge/deployment-with-makefile.md)
- [ECSタスクへの接続とマイグレーション実行手順](../knowledge/ecs-task-connection-and-migration.md)

