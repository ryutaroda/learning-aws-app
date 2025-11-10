# ECSサービス設定ドキュメント

## 作成順序

### 前提条件

1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`
3. ✅ **環境変数設定完了**: S3バケット・Secrets Manager
4. ✅ **ECRイメージ作成完了**: `learning-app-stg`
5. ✅ **ECSクラスター作成完了**: `learning-cluster-stg`
6. ✅ **IAMロール作成完了**: タスクロール・タスク実行ロール
7. ✅ **セキュリティグループ作成完了**: ECSタスク用・ALB用
8. ✅ **タスク定義作成完了**: `learning-app-task-stg`

### 作成手順

#### 1. ECSサービス作成

- [X] **サービス基本設定**
  - 起動タイプ: Fargate
  - サービス名: `learning-ecs-service-stg`
  - タスク定義: `learning-app-task-stg:latest`
  - サービスタイプ: レプリカ
  - タスク数: 1（初期値、後でオートスケーリング設定可能）

- [X] **デプロイメント設定**
  - デプロイメントタイプ: ローリングアップデート
  - 最小ヘルス率: 100%
  - 最大ヘルス率: 200%
  - デプロイメント回路ブレーカー: 有効
    - ロールバック: 有効

- [X] **ネットワーキング設定**
  - VPC: `learning-stg`
  - サブネット: **プライベート（NAT）サブネット** を選択
    - `private-subnet-nat-1a-stg` (10.0.16.0/20) - ap-northeast-1a
    - `private-subnet-nat-1c-stg` (10.0.80.0/20) - ap-northeast-1c
    - ⚠️ パブリックサブネットやRDS用プライベートサブネットは選択しない
  - セキュリティグループ: `learning-app-sg-stg`
  - パブリックIPの自動割り当て: **無効（DISABLED）**
    - プライベートサブネットではパブリックIPは不要
    - アウトバウンド通信はNATゲートウェイ経由
    - インバウンド通信はALB経由

- [X] **ロードバランシング設定**
  - ALB作成した後に以下を対応する
  - ロードバランサータイプ: Application Load Balancer
  - コンテナとポート:
    - コンテナ名: `app`
    - コンテナポート: `80`
    - ホストポート: `80`（Fargateではコンテナポートと同じ）
    - 形式: `app 80:80`（Host port:Container port）
  - ロードバランサー: `learning-alb-stg` を選択
    - スキーム: インターネット向け（internet-facing）
  - リスナー: `HTTPS:443` を選択
  - ターゲットグループ: `learning-tg-stg` を選択
    - ターゲットタイプ: IP

---

## 参考リンク

- [ECSセットアップドキュメント](./5_ecs-setup.md)
- [ECSタスク定義ドキュメント](./6_ecs-task-definition.md)
- [APP_URLとヘルスチェックの違い](../app-url-and-health-check.md)
- [AWS ECS サービスのドキュメント](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/ecs_services.html)
