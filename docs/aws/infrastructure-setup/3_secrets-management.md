# 環境変数設定ドキュメント

## 作成順序

### 前提条件
1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **RDS作成完了**: `learning-db-stg`

### 作成手順
1. [x] **S3バケット作成**: 環境変数ファイル保存用
   - 設定ファイル格納バケット
   - learning-ryutaro-config-stg
   - バージョニング設定
   - learning-stg.env（ファイル名）
   - ファイル内の記載は下記の形式

   ```
    POSTGRES_MAIN_USE_MOCK=true
    API_LOG_LEVEL=debug
    API_ENABLE_LOG_EXCLUSION=true
   ```
2. [x] **Secrets Manager設定**: 機密情報管理
   - データベース認証情報
   - マネコンから登録
   - Other type of secrets
   - Secret value
      - host : rds host
      - operator_user : postgres
      - operator_password : rds作成時のパスワード
   - Secret name : db-main-instance-stg

## まとめ

### 構成概要
- **S3バケット**: 環境変数ファイル（learning-stg.env）の保存
- **Secrets Manager**: データベース認証情報の安全な管理

### 作成したリソース
| リソース | 名前 | 用途 |
|---------|------|------|
| S3バケット | learning-ryutaro-config-stg | 環境変数ファイル保存 |
| 環境変数ファイル | learning-stg.env | アプリケーション設定 |
| Secrets Manager | db-main-instance-stg | DB認証情報管理 |

### セキュリティ設計
- **機密情報分離**: パスワード等はSecrets Managerで管理
- **設定情報**: 非機密な環境変数はS3で管理
- **バージョン管理**: S3バケットでバージョニング有効化

### 設定内容
**環境変数（S3）**: (下記は例)
- POSTGRES_MAIN_USE_MOCK=true
- API_LOG_LEVEL=debug
- API_ENABLE_LOG_EXCLUSION=true

**機密情報（Secrets Manager）**:
- host: RDSエンドポイント
- operator_user: postgres
- operator_password: RDS作成時のパスワード

