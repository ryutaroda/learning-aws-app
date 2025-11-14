# 踏み台サーバー（Bastion Host）構築ドキュメント

## 概要

プライベートサブネット内のリソース（RDS、ECSタスクなど）に安全にアクセスするための踏み台サーバー（Bastion Host）を作成します。SSM Session Managerを使用することで、SSHキーなしでセキュアに接続できます。

## 作成順序

### 前提条件

1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **パブリックサブネット作成完了**: `public-subnet-1a-stg`
3. ✅ **RDS作成完了**: `learning-db-stg`
4. ✅ **ECSクラスター作成完了**: `learning-cluster-stg`
5. ✅ **セキュリティグループ作成完了**: RDS用セキュリティグループ

### 作成手順

**注意**: 以下の手順はすべてAWSマネジメントコンソール上で実施します。

#### 1. 踏み台サーバー用のセキュリティグループを作成する

- [x] **踏み台サーバー用セキュリティグループ作成**
  - EC2コンソール → セキュリティグループ → セキュリティグループを作成
  - セキュリティグループ名: `learning-bastion-sg-stg`
  - 説明: 踏み台サーバー用セキュリティグループ
  - VPC: `learning-stg`

- [x] **インバウンドルール設定**

  **SSM Session Managerを使用する場合（推奨）**:
  
  **インバウンドルールの設定は不要です。**
  
  SSM Session Managerを使用する場合、接続はAWS Systems Manager経由で行われるため、セキュリティグループでポートを開ける必要はありません。IAMロールとSSM Agentが正しく設定されていれば、セキュリティグループのインバウンドルールは空のままで問題ありません。

- [x] **アウトバウンドルール設定**
  
  | タイプ | プロトコル | ポート範囲 | 宛先 | 説明 |
  |--------|-----------|-----------|------|------|
  | All traffic | All | All | 0.0.0.0/0 | すべてのアウトバウンド通信を許可 |

  **注意**: 踏み台サーバーからRDSやECSタスクに接続するため、アウトバウンド通信はすべて許可します。

#### 2. RDSセキュリティグループに踏み台サーバーからの接続を許可する

- [x] **RDSセキュリティグループの更新**
  - RDSコンソール → データベース → `learning-db-stg` → 「接続とセキュリティ」タブ
  - セキュリティグループ: `learning-db-sg-stg` を選択
  - セキュリティグループの編集
  - インバウンドルールに追加:
  
  | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
  |--------|-----------|-----------|--------|------|
  | PostgreSQL | TCP | 5432 | `sg-xxxxxx`（踏み台サーバーのセキュリティグループID） | 踏み台サーバーからの接続 |

#### 3. 踏み台サーバー用のIAMロールを作成する

SSM Session Manager経由で接続できるように、IAMロールとインスタンスプロファイルを作成します。

- [x] **IAMロール作成**
  - IAMコンソール → ロール → ロールを作成
  - ロール名: `learning-bastion-role-stg`
  - 信頼されたエンティティタイプ: AWS のサービス
  - ユースケース: EC2
  - 信頼ポリシー: EC2サービスを信頼（自動設定）

- [x] **ポリシーの付与**
  - 付与するポリシー:
    - `AmazonSSMManagedInstanceCore` - SSM経由でのインスタンス管理に必要
  - ロールを作成

- インスタンスプロファイルは自動的に作成される（EC2ロール作成時に自動生成）

#### 4. 踏み台サーバー用のEC2インスタンスを作成する

- [x] **踏み台サーバーを起動**
  - EC2コンソール → インスタンス → インスタンスを起動
  - 名前: `learning-bastion-stg`
  - AMI: Amazon Linux 2023またはAmazon Linux 2（最新版）
  - インスタンスタイプ: t3.micro（学習環境のため最小構成）

- [x] **ネットワーク設定**
  - VPC: `learning-stg`
  - サブネット: `public-subnet-1a-stg`（パブリックサブネット）
  - パブリックIPの自動割り当て: **有効化**
  - セキュリティグループ: `learning-bastion-sg-stg` を選択

- [x] **ストレージ設定**
  - ルートボリューム: 8GB GP3（最小推奨 デフォルト）

- [x] **詳細設定**
  - IAMインスタンスプロファイル: `learning-bastion-role-stg` を選択
  - これにより、SSM Session Manager経由でインスタンスに接続可能になります（SSHキー不要）

- [x] **インスタンスを起動**
  - インスタンス状態が「実行中」になるまで待機
  - ステータスチェックが成功するまで待機
  - キーペア: **「Proceed without key pair」を選択**（SSM接続を使用するため不要）

**SSM接続のメリット**:
- SSHキーが不要
- セキュリティグループでSSHポート（22）を開ける必要がない
- AWS Systems Manager Session Manager経由でブラウザから接続可能
- 接続ログがCloudWatch Logsに記録される

#### 5. Elastic IPの割り当て（SSH接続を使用する場合のみ）

**SSM Session Managerを使用する場合（推奨）**:
- Elastic IPの割り当ては**不要**です。
- SSM Session ManagerはインスタンスIDで接続するため、IPアドレスの変更は影響しません。
- セキュリティグループでインバウンドルールを設定する必要もないため、IPアドレスを固定する必要はありません。

**SSH接続を使用する場合**:
踏み台サーバーのIPアドレスを固定する場合、Elastic IPを割り当てます。

- [ ] **Elastic IPの割り当て**
  - EC2コンソール → Elastic IP → Elastic IPアドレスを割り当て
  - ネットワークボーダーグループ: デフォルト
  - Elastic IPアドレスを割り当て

- [ ] **Elastic IPの関連付け**
  - 割り当てたElastic IPを選択 → 「アクション」→「Elastic IPアドレスの関連付け」
  - インスタンス: `learning-bastion-stg` を選択
  - プライベートIPアドレス: 自動選択
  - 関連付け

**Elastic IPのメリット（SSH接続の場合）**:
- IPアドレスが固定される
- インスタンス再起動後もIPアドレスが変わらない
- SSH接続時の設定が簡単

## GUIツールからRDSに接続する方法

TablePlusやpgAdminなどのGUIツールからRDSに接続するために、踏み台サーバー経由でポートフォワーディングを設定します。

### SSM Session Managerポートフォワーディング（推奨）

**メリット**:
- SSHキー不要
- セキュリティグループでSSHポートを開ける必要がない
- 接続ログがCloudWatch Logsに記録される

#### セットアップ手順

```bash
# 1. 踏み台サーバーのインスタンスIDを取得
INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=tag:Name,Values=learning-bastion-stg" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text \
  --region ap-northeast-1)

# 2. RDSエンドポイントを取得
RDS_ENDPOINT=$(aws rds describe-db-instances \
  --db-instance-identifier learning-db-stg \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text \
  --region ap-northeast-1)

# 3. SSM Session Managerでポートフォワーディングを開始
aws ssm start-session \
  --target $INSTANCE_ID \
  --document-name AWS-StartPortForwardingSessionToRemoteHost \
  --parameters "{\"host\":[\"${RDS_ENDPOINT}\"],\"portNumber\":[\"5432\"],\"localPortNumber\":[\"5433\"]}" \
  --region ap-northeast-1
```

**パラメータの説明**:
- `host`: RDSのエンドポイント
- `portNumber`: RDSのポート（PostgreSQLは5432）
- `localPortNumber`: ローカルで使用するポート（5433など、既存のPostgreSQLと競合しないポート）

**注意**: ポートフォワーディングは接続を維持している間のみ有効です。接続を切断するとポートフォワーディングも終了します。

#### TablePlusでの接続設定

```
ホスト: localhost
ポート: 5433（localPortNumberで指定したポート）
ユーザー名: postgres
パスワード: （Secrets Managerから取得）
データベース: kanaoka
```

#### 便利なスクリプト

`scripts/port-forward-rds.sh` を作成:

```bash
#!/bin/bash
# scripts/port-forward-rds.sh

ENV=${1:-stg}
REGION=ap-northeast-1

# 踏み台サーバーのインスタンスIDを取得
INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=tag:Name,Values=learning-bastion-${ENV}" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text \
  --region ${REGION})

if [ -z "$INSTANCE_ID" ] || [ "$INSTANCE_ID" = "None" ]; then
  echo "Error: Bastion host not found"
  exit 1
fi

# RDSエンドポイントを取得
RDS_ENDPOINT=$(aws rds describe-db-instances \
  --db-instance-identifier learning-db-${ENV} \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text \
  --region ${REGION})

if [ -z "$RDS_ENDPOINT" ] || [ "$RDS_ENDPOINT" = "None" ]; then
  echo "Error: RDS endpoint not found"
  exit 1
fi

echo "Starting port forwarding..."
echo "Local port: 5433"
echo "RDS endpoint: ${RDS_ENDPOINT}"
echo ""
echo "Connect to GUI with:"
echo "  Host: localhost"
echo "  Port: 5433"
echo "  Database: kanaoka"
echo "  Username: postgres"
echo ""
echo "Press Ctrl+C to stop port forwarding"

aws ssm start-session \
  --target ${INSTANCE_ID} \
  --document-name AWS-StartPortForwardingSessionToRemoteHost \
  --parameters "{\"host\":[\"${RDS_ENDPOINT}\"],\"portNumber\":[\"5432\"],\"localPortNumber\":[\"5433\"]}" \
  --region ${REGION}
```

**使用方法**:
```bash
chmod +x scripts/port-forward-rds.sh
./scripts/port-forward-rds.sh stg
```

## 動作確認

- [ ] **ポートフォワーディングの確認**
  - SSM Session Managerでポートフォワーディングを開始
  - TablePlusやpgAdminなどのGUIツールからRDSに接続できることを確認


## 参考リンク

- [VPC設計ドキュメント](./1_vpc.md)
- [RDS設計ドキュメント](./2_rds.md)
- [ECS設定ドキュメント](./5_ecs-setup.md)
- [AWS Systems Manager Session Manager ドキュメント](https://docs.aws.amazon.com/ja_jp/systems-manager/latest/userguide/session-manager.html)
- [ECS Exec ドキュメント](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/ecs-exec.html)

