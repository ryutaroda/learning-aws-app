# RDS設計・構築ドキュメント

## 作成順序

### 前提条件
1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **プライベートサブネット作成完了**: RDS用サブネット×2

### 作成手順
1. [x] **セキュリティグループ作成**: `learning-db-sg-stg`
   - MySQL 3306ポートをECS用SGから許可
   - この段階では紐付きがないのでインバウンドルールの設定は不要
   - アウトバウンドはデフォルトのまま登録
2. [x] **DBサブネットグループ作成**: `learning-db-subnet-group-stg`
   - プライベートサブネット（1a, 1c）を含める
   - 追加するsubnetのAZは作成したsubnetのprivate1a,private1cを選択
3. [x] **パラメータグループ**: `learning-db-parameter-group-stg`
   - Engine type : PostgreSQL
   - Parameter group family : postgres16
   - Type : DB Parameter group (クラスターではなくDBの)
3. [x] **RDSインスタンス作成**: `learning-db-stg`
   - Engine: PostgreSQL 16
   - Single DB instance
   - Self managed パスワード
   - Master password: kanaokaharumaro
   - VPC: learning-stg を選択
   - AZ: ap-northeast-1a
   - Parameter group: learning-db-parameter-group-stg を選択
   - 初期DB名: kanaoka
   - Backup window: 16:00-17:00

## 基本設定

**DB識別子**: `learning-db-stg`
**エンジンタイプ**: PostgreSQL 16
**インスタンスクラス**: db.t3.micro（学習用）
**ストレージタイプ**: gp3（汎用SSD）
**割り当てストレージ**: 20 GB
**Deployment options**: Single DB instance
**可用性ゾーン**: ap-northeast-1a

## ネットワーク設定

### VPC設定
**VPC**: `learning-stg` (10.0.0.0/16)
**サブネットグループ**: `learning-db-subnet-group-stg`

### 配置サブネット
| サブネット名 | AZ | CIDR | 用途 |
|-------------|-----|------|------|
| private-subnet-1a-stg | ap-northeast-1a | 10.0.32.0/20 | RDS プライマリ |
| private-subnet-1c-stg | ap-northeast-1c | 10.0.96.0/20 | RDS スタンバイ |

### セキュリティグループ
**名前**: `learning-db-sg-stg`
**説明**: RDS PostgreSQL用セキュリティグループ

- この段階では紐付きがないのでインバウンドルールの設定は不要

#### インバウンドルール（後で下記のように設定）
| タイプ | プロトコル | ポート | 送信元 | 説明 |
|--------|-----------|--------|--------|------|
| PostgreSQL | TCP | 5432 | sg-xxxxxxxxx (ECS用SG) | ECSタスクからの接続 |

#### アウトバウンドルール
| タイプ | プロトコル | ポート | 送信先 | 説明 |
|--------|-----------|--------|--------|------|
| すべてのトラフィック | すべて | すべて | 0.0.0.0/0 | デフォルト（削除可能） |

## データベース設定

### 認証情報
**マスターユーザー名**: `postgres`（デフォルト）
**パスワード管理**: Self managed
**マスターパスワード**: `kanaokaharumaro`
**初期データベース名**: `kanaoka`

### 接続設定
**ポート**: 5432（PostgreSQL標準）
**パブリックアクセス**: 無効
**VPC内からのみアクセス可能**

## 可用性・バックアップ設定

### Multi-AZ配置
**Multi-AZ**: 無効（学習用・コスト削減）
**注意**: 本番環境では有効化を推奨

### バックアップ設定
**自動バックアップ**: 有効
**バックアップ保持期間**: 7日間
**バックアップウィンドウ**: 16:00-17:00 JST
**メンテナンスウィンドウ**: 日曜 04:00-05:00 JST

### 暗号化
**保存時の暗号化**: 有効
**KMSキー**: AWS管理キー

## パフォーマンス・監視

### パフォーマンスインサイト
**Performance Insights**: 有効
**保持期間**: 7日間（無料枠）

### 拡張モニタリング
**拡張モニタリング**: 無効（学習用）
**注意**: 本番環境では有効化を推奨

### ログエクスポート
**エラーログ**: 有効
**一般ログ**: 無効（学習用）
**スロークエリログ**: 有効
**バイナリログ**: 無効

## セキュリティ考慮事項

### ネットワークセキュリティ
- プライベートサブネットに配置（インターネットアクセス不可）
- セキュリティグループで接続元を制限
- NACLによる追加制御（必要に応じて）

### 認証・認可
- Secrets Managerによるパスワード管理
- IAMデータベース認証（オプション）
- SSL/TLS接続の強制（推奨）

### 監査・ログ
- CloudTrailによるAPI操作ログ
- RDSログのCloudWatch Logs出力
- Performance Insightsによる性能監視

## 接続情報

### エンドポイント
**エンドポイント**: `learning-db-stg.xxxxxxxxxx.ap-northeast-1.rds.amazonaws.com`
**ポート**: 5432

### 接続例（ECSタスクから）
```bash
# PostgreSQL CLI接続例
psql -h learning-db-stg.xxxxxxxxxx.ap-northeast-1.rds.amazonaws.com \
     -p 5432 \
     -U postgres \
     -d kanaoka
```

### アプリケーション設定例
```env
DB_HOST=learning-db-stg.xxxxxxxxxx.ap-northeast-1.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=kanaoka
DB_USERNAME=postgres
DB_PASSWORD=kanaokaharumaro
```

## コスト最適化

### 学習用設定
- **インスタンスクラス**: db.t3.micro（最小構成）
- **Multi-AZ**: 無効（単一AZ）
- **拡張モニタリング**: 無効
- **バックアップ保持**: 7日間（最小）

### 月額コスト概算
- **RDS インスタンス**: 約$15/月
- **ストレージ（20GB）**: 約$2/月
- **バックアップストレージ**: 約$1/月
- **合計**: 約$18/月

### 節約Tips
- 使用しない時間帯はインスタンス停止
- 不要なログ出力を無効化
- 定期的なストレージ使用量確認

## トラブルシューティング

### 接続できない場合
1. セキュリティグループの確認
2. サブネットグループの確認
3. VPCルートテーブルの確認
4. DNS解決の確認

### パフォーマンス問題
1. Performance Insightsの確認
2. スロークエリログの分析
3. 接続数の監視
4. インスタンスクラスの見直し

## 次のステップ

- [ ] **ECS連携設定**
- [ ] **アプリケーションデプロイ**
- [ ] **データベーススキーマ作成**
- [ ] **初期データ投入**
- [ ] **バックアップ・復旧テスト**
