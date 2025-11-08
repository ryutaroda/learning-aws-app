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
  - immutable
  - ライフサイクルポリシー設定
  ```json
  {
    "rules": [
        {
        "rulePriority": 1,
        "description": "最新の3世代のイメージのみを保持",
        "selection": {
            "tagStatus": "any",
            "countType": "imageCountMoreThan",
            "countNumber": 3
        },
        "action": {
            "type": "expire"
        }
        }
    ]
    }
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
  - イメージサイズ・タグ確認(タグはGithubのコミットの)
