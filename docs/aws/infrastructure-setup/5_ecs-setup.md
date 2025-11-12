# ECS設定ドキュメント

## 作成順序

### 前提条件
1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`
3. ✅ **環境変数設定完了**: S3バケット・Secrets Manager
4. ✅ **ECRイメージ作成完了**: `learning-app-stg`

### 作成手順

#### 1. ECSクラスター作成
- [x] **クラスター作成**
  - クラスター名: `learning-cluster-stg`
  - インフラストラクチャ: AWS Fargate（Fargate only）
  - クラスター設定:
    - ネットワーキング: VPC `learning-stg` を使用
    - CloudWatch Container Insights: 有効化（オプション）

#### 2. セキュリティグループ設定

入ってくれば出ていくのでアウトバウンドは基本デフォルト（0.0.0.0/0）のまま

- [x] **ECSサービス用セキュリティグループ作成**
  - セキュリティグループ名: `learning-app-sg-stg`
  - VPC: `learning-stg`
  - インバウンドルール:
    - ALB用のSG IDからのトラフィック（ポート80）- WEB用
      - HTTP
      - TCP
    - ALB用のSG IDからのトラフィック（ポート8080）- API用 **今回は不要**

- [x] **ALB用セキュリティグループ作成**
  - セキュリティグループ名: `learning-alb-sg-stg`
  - VPC: `learning-stg`
  - インバウンドルール:
    - 0.0.0.0/0からのHTTPS通信（ポート443）
      - インターネットからのアクセスがあるため0.0.0.0/0

- [x] **RDS用セキュリティグループ設定更新**
  - セキュリティグループ名: `learning-db-sg-stg`（既存）
  - インバウンドルールに追加:
    - ECSサービス用のSG IDからのトラフィック（ポート5432）

#### 3. IAMロール作成
- [x] **ECSタスク実行ロール作成**
  - ロール名: `learning-ecs-task-execution-role-stg`
  - 信頼ポリシー: ECSタスク実行を許可
  - 付与するポリシー:
    - `AmazonECSTaskExecutionRolePolicy`
    - `AmazonS3ReadOnlyAccess`
    - `secrets-manager-readonly-stg`
        ``` 
        "secretsmanager:GetRandomPassword",
        "secretsmanager:GetResourcePolicy",
        "secretsmanager:GetSecretValue",
        "secretsmanager:DescribeSecret",
        "secretsmanager:ListSecretVersionIds"
        ```
  - **インラインポリシー**:
    - ポリシー名: `S3EnvFileAccess`
    - 用途: ECSタスク定義で `environmentFiles` を使用してS3から環境変数ファイル（`.env`）を読み込むための権限
    - 設定内容:
      ```json
      {
        "Version": "2012-10-17",
        "Statement": [
          {
            "Effect": "Allow",
            "Action": [
              "s3:GetObject"
            ],
            "Resource": [
              "arn:aws:s3:::learning-ryutaro-config-stg/learning-stg.env"
            ]
          },
          {
            "Effect": "Allow",
            "Action": [
              "s3:GetBucketLocation"
            ],
            "Resource": [
              "arn:aws:s3:::learning-ryutaro-config-stg"
            ]
          }
        ]
      }
      ```
    - 説明:
      - `s3:GetObject`: S3バケット内の `learning-stg.env` ファイルを読み取る権限
      - `s3:GetBucketLocation`: バケットのリージョンを取得する権限（S3アクセス時に必要）
      - 最小権限の原則に従い、特定のファイルのみへのアクセスを許可

- [x] **ECSタスクロール作成**
  - ロール名: `learning-ecs-task-role-stg`
  - 信頼ポリシー: ECSタスクを許可
  - 付与するポリシー:
    - アプリケーションが必要なAWSサービスへのアクセス権限
    - `AmazonSSMManagedInstanceCore`
    - `CloudWatchLogsFullAccess`

#### 4. タスク定義作成
- [ ] **タスク定義作成**
  - 詳細は `6_ecs-task-definition.md` を参照



