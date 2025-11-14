# GitHub Actions設定ドキュメント

## 概要

GitHub Actionsを使用してECSへの自動デプロイを設定します。`main`ブランチへのプッシュ時に自動的にデプロイが実行されます。

## 前提条件

1. ✅ **AWSアカウント設定完了**
2. ✅ **ECSクラスター・サービス作成完了**
3. ✅ **ECRリポジトリ作成完了**
4. ✅ **IAMロール作成完了**（GitHub Actions用）

## セットアップ手順

### 1. IAMロールの作成（GitHub Actions用）

GitHub ActionsからAWSリソースにアクセスするためのIAMロールを作成します。

#### 1-0. OIDCプロバイダーの作成（初回のみ）

`token.actions.githubusercontent.com`が表示されない場合は、OIDCプロバイダーを先に作成する必要があります。

- IAMコンソール → **アイデンティティプロバイダー** → **プロバイダーを作成**
- **プロバイダータイプ**: OpenID Connect
- **プロバイダーURL**: `https://token.actions.githubusercontent.com`
- **オーディエンス**: `sts.amazonaws.com`
- **プロバイダーを作成**

**注意**: OIDCプロバイダーはAWSアカウントごとに1回だけ作成すれば、複数のIAMロールで使用できます。

#### 1-1. IAMロールの作成

- IAMコンソール → ロール → ロールを作成
- **ロール名**: `learning-github-actions-role-stg`
- 信頼されたエンティティタイプ: **Webアイデンティティ**
- アイデンティティプロバイダー: `token.actions.githubusercontent.com`
- オーディエンス: `sts.amazonaws.com`
- 条件: GitHubリポジトリを指定
  ```json
  {
    "StringEquals": {
      "token.actions.githubusercontent.com:aud": "sts.amazonaws.com"
    },
    "StringLike": {
      "token.actions.githubusercontent.com:sub": "repo:YOUR_GITHUB_USERNAME/YOUR_REPO_NAME:*"
    }
  }
  ```

#### 1-2. ポリシーの付与

以下のポリシーを付与します:

- **ECRへのアクセス権限**:
  - `AmazonEC2ContainerRegistryFullAccess` またはカスタムポリシー
- **ECSへのアクセス権限**:
  - `AmazonECS_FullAccess` またはカスタムポリシー
- **その他の必要な権限**:
  - CloudWatch Logsへの書き込み権限
  - Secrets Managerへの読み取り権限（必要に応じて）

#### 1-3. カスタムポリシーの例

最小権限の原則に従ったカスタムポリシー:

- **ポリシー名**: `learning-github-actions-stg`

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ecr:GetAuthorizationToken",
        "ecr:BatchCheckLayerAvailability",
        "ecr:GetDownloadUrlForLayer",
        "ecr:BatchGetImage",
        "ecr:PutImage",
        "ecr:InitiateLayerUpload",
        "ecr:UploadLayerPart",
        "ecr:CompleteLayerUpload"
      ],
      "Resource": "*"
    },
    {
      "Effect": "Allow",
      "Action": [
        "ecs:DescribeTaskDefinition",
        "ecs:RegisterTaskDefinition",
        "ecs:UpdateService",
        "ecs:DescribeServices",
        "ecs:ListTasks",
        "ecs:DescribeTasks",
        "ecs:ExecuteCommand"
      ],
      "Resource": "*"
    },
    {
      "Effect": "Allow",
      "Action": [
        "iam:PassRole"
      ],
      "Resource": [
        "arn:aws:iam::*:role/learning-ecs-task-execution-role-*",
        "arn:aws:iam::*:role/learning-ecs-task-role-*"
      ]
    },
    {
      "Effect": "Allow",
      "Action": [
        "logs:CreateLogStream",
        "logs:PutLogEvents"
      ],
      "Resource": "arn:aws:logs:*:*:*"
    },
    {
        "Effect": "Allow",
        "Action": [
            "sts:GetCallerIdentity"
        ],
        "Resource": "*"
    }
  ]
}
```

### 2. GitHub Secretsの設定

GitHubリポジトリのSettings → Secrets and variables → Actions で以下を設定:

#### OIDCを使用

#### 2-1. AWS_ROLE_ARN

- **Name**: `AWS_ROLE_ARN`
- **Value**: 作成したIAMロールのARN
  - 例: `arn:aws:iam::123456789012:role/learning-github-actions-role-stg`

### 3. GitHub Actionsワークフローの確認

`.github/workflows/deploy.yml` が正しく配置されているか確認します。

## ワークフローの動作

### 自動デプロイ

- **トリガー**: `develop`ブランチへのプッシュ
- **環境**: `stg`（デフォルト）
- **実行内容**:
  1. コードのチェックアウト
  2. AWS認証情報の設定（OIDC）
  3. Dockerイメージのビルド
  4. ECRへのプッシュ
  5. ECSサービスの更新
  6. データベースマイグレーションの実行

### 手動デプロイ

- **トリガー**: GitHub Actionsの「Run workflow」ボタン
- **環境**: `stg`または`prd`を選択可能
- **実行内容**: 自動デプロイと同じ

## セキュリティのベストプラクティス

1. **最小権限の原則**: IAMロールには必要最小限の権限のみを付与
2. **OIDCの使用**: アクセスキーではなくOIDCを使用（推奨）
3. **リポジトリの指定**: 信頼ポリシーで特定のリポジトリのみを許可
4. **環境変数の保護**: 機密情報はGitHub Secretsに保存

## 参考リンク

- [GitHub Actions ドキュメント](https://docs.github.com/ja/actions)
- [AWS IAM OIDC プロバイダー](https://docs.aws.amazon.com/ja_jp/IAM/latest/UserGuide/id_roles_providers_create_oidc.html)
- [AWS ECS デプロイメント](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/deployment.html)
- [Makefileデプロイ手順ドキュメント](./deployment-with-makefile.md)

