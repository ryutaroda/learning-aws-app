# VPC設計ドキュメント

## 基本設定

**VPC名**: `learning-stg`
**VPC CIDR**: `10.0.0.0/16`
- 利用可能IP数: 約65,536個
- サブネット分割: /20 (約4,096個ずつ)
- **Available VPCs**: VPCをアベイラビリティーゾーンで利用可能に設定（VPCをアタッチ）

## ゲートウェイ設定

### インターネットゲートウェイ
**名前**: `learning-igw-stg`
- パブリックサブネットからのインターネットアクセス用

### NATゲートウェイ
**名前**: `learning-nat-1a-stg`
- **Connectivity type**: Public
- **Elastic IP**: 自動割り当て
- プライベートサブネットからのアウトバウンド通信用
- 学習ようなので1aだけに配置

## サブネット設計

### AZ-1a (ap-northeast-1a)

| サブネット名 | CIDR | 用途 | 利用可能IP数 |
|-------------|------|------|-------------|
| public-subnet-1a-stg | `10.0.0.0/20` | パブリック | 約4,000 |
| private-subnet-nat-1a-stg | `10.0.16.0/20` | ECS | 約4,000 |
| private-subnet-1a-stg | `10.0.32.0/20` | RDS | 約4,000 |

### AZ-1c (ap-northeast-1c)

| サブネット名 | CIDR | 用途 | 利用可能IP数 |
|-------------|------|------|-------------|
| public-subnet-1c-stg | `10.0.64.0/20` | パブリック | 約4,000 |
| private-subnet-nat-1c-stg | `10.0.80.0/20` | ECS | 約4,000 |
| private-subnet-1c-stg | `10.0.96.0/20` | RDS | 約4,000 |

## 設計のポイント

### 冗長性
- 2つのAZ（1a, 1c）を使用してマルチAZ構成
- 各AZに同じ構成のサブネットを配置

### セキュリティ層
1. **パブリックサブネット**: インターネットゲートウェイ経由でインターネットアクセス
2. **プライベート（NAT）サブネット**: NATゲートウェイ（もしくはNatインスタンス）経由でアウトバウンドのみ
3. **プライベートサブネット**: 完全にプライベート（RDS用）

### 拡張性
- 現在6サブネット使用
- /16 VPCで最大16個の/20サブネットまで作成可能
- 将来的な拡張に十分な余裕あり

## ルートテーブル設定

### 1. パブリックサブネット用
**名前**: `learning-rtb-public-stg`
**ルート設定**:
- `10.0.0.0/16` → Local
- `0.0.0.0/0` → `learning-igw-stg`

**関連付けサブネット**:
- `public-subnet-1a-stg`
- `public-subnet-1c-stg`

### 2. プライベート（NAT）サブネット用
**名前**: `learning-rtb-private-nat-stg`
**ルート設定**:
- `10.0.0.0/16` → Local
- `0.0.0.0/0` → `learning-nat-stg`

**関連付けサブネット**:
- `private-subnet-nat-1a-stg`
- `private-subnet-nat-1c-stg`

### 3. プライベート（RDS）サブネット用
**名前**: `learning-rtb-private-stg`
**ルート設定**:
- `10.0.0.0/16` → Local

**関連付けサブネット**:
- `private-subnet-1a-stg`
- `private-subnet-1c-stg`

## 作成順序

### 完了済み
1. ✅ **VPC作成**: `learning-stg` (10.0.0.0/16)
2. ✅ **サブネット作成**: 6つのサブネット（パブリック×2、プライベートNAT×2、プライベート×2）
3. ✅ **インターネットゲートウェイ作成**: `learning-igw-stg`
4. ✅ **NATゲートウェイ作成**: `learning-nat-1a-stg` (Public, Elastic IP自動割り当て)

### 次のステップ
5. [ ] **ルートテーブル作成・設定**
   - `learning-rtb-public-stg`（パブリックサブネット用）
   - `learning-rtb-private-nat-stg`（プライベートNATサブネット用）
   - `learning-rtb-private-stg`（プライベートRDSサブネット用）
6. [ ] **セキュリティグループ作成**
7. [ ] **EC2インスタンス作成・配置**
8. [ ] **RDS作成・配置**

## 次のステップ

- [ ] Terraformコードの作成
- [ ] ルートテーブルの設定
- [ ] セキュリティグループの設計
- [ ] NATゲートウェイの配置検討
