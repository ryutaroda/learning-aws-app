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

## 接続方法

### SSM Session Managerを使用（推奨）

**前提条件**: AWS CLIとSession Manager Pluginがインストールされていること

```bash
# Session Manager Pluginのインストール（macOS）
brew install --cask session-manager-plugin

# 踏み台サーバーに接続
aws ssm start-session \
  --target i-xxxxxxxxxxxxx \
  --region ap-northeast-1

# インスタンスIDの確認
aws ec2 describe-instances \
  --filters "Name=tag:Name,Values=learning-bastion-stg" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text \
  --region ap-northeast-1
```

**ブラウザから接続**:
- EC2コンソール → インスタンス → `learning-bastion-stg` を選択
- 「接続」ボタンをクリック
- 「Session Manager」タブを選択
- 「接続」をクリック

**接続確認済み**

## RDSへの接続

踏み台サーバーからRDSに接続する方法です。

### PostgreSQLへの接続

```bash
# 踏み台サーバーに接続（SSM Session Manager）
aws ssm start-session --target i-xxxxxxxxxxxxx --region ap-northeast-1

# PostgreSQLクライアントのインストール（Amazon Linux 2023）
sudo dnf install -y postgresql15

# RDSに接続
psql -h learning-db-stg.xxxxxxxxxx.ap-northeast-1.rds.amazonaws.com \
     -U postgres \
     -d kanaoka

# パスワード入力（Secrets Managerから取得したパスワード）
```

### 接続確認

```bash
# データベース一覧を表示
\l

# テーブル一覧を表示
\dt

# 接続を終了
\q
```

## ポートフォワーディングでGUIツールからRDSに接続

TablePlusやpgAdminなどのGUIツールからRDSに接続するために、踏み台サーバー経由でポートフォワーディングを設定します。

### 方法1: SSM Session Managerポートフォワーディング（推奨）

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
echo "Connect to TablePlus/pgAdmin with:"
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

### 方法2: SSHポートフォワーディング

**前提条件**:
- SSHキーペアを作成してインスタンスに割り当て
- セキュリティグループでSSHポート（22）を開ける
- 自分のIPアドレスからの接続を許可

#### セットアップ手順

```bash
# 1. 踏み台サーバーのElastic IPまたはパブリックIPを取得
BASTION_IP=$(aws ec2 describe-instances \
  --filters "Name=tag:Name,Values=learning-bastion-stg" \
  --query 'Reservations[0].Instances[0].PublicIpAddress' \
  --output text \
  --region ap-northeast-1)

# 2. RDSエンドポイントを取得
RDS_ENDPOINT=$(aws rds describe-db-instances \
  --db-instance-identifier learning-db-stg \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text \
  --region ap-northeast-1)

# 3. SSHポートフォワーディングを開始
ssh -i ~/.ssh/your-key.pem \
    -L 5433:${RDS_ENDPOINT}:5432 \
    ec2-user@${BASTION_IP} \
    -N

# オプション:
# -L: ローカルポートフォワーディング
# 5433: ローカルポート
# ${RDS_ENDPOINT}:5432: RDSのエンドポイントとポート
# -N: コマンドを実行せず、ポートフォワーディングのみ
```

#### TablePlusでの接続設定

```
ホスト: localhost
ポート: 5433
ユーザー名: postgres
パスワード: （Secrets Managerから取得）
データベース: kanaoka
```

## ECSタスクへの接続

踏み台サーバーからECSタスクに接続する方法です。

### ECS Execを使用（推奨）

踏み台サーバー経由ではなく、直接ECS Execを使用することを推奨します。

```bash
# ECSタスクIDを取得
TASK_ARN=$(aws ecs list-tasks \
  --cluster learning-cluster-stg \
  --service-name learning-ecs-service-stg \
  --query 'taskArns[0]' \
  --output text \
  --region ap-northeast-1)

TASK_ID=${TASK_ARN##*/}

# ECSタスクに接続
aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task $TASK_ID \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1
```

## 動作確認

- [ ] **踏み台サーバーの起動確認**
  - EC2コンソールでインスタンスが「実行中」であることを確認
  - ステータスチェックが成功していることを確認

- [ ] **SSM接続の確認**
  - SSM Session Manager経由で接続できることを確認
  - または、SSH接続で接続できることを確認

- [ ] **RDS接続の確認**
  - 踏み台サーバーからRDSに接続できることを確認
  - PostgreSQLクライアントでデータベースに接続できることを確認

- [ ] **ポートフォワーディングの確認**
  - SSM Session ManagerまたはSSHでポートフォワーディングを開始
  - TablePlusやpgAdminなどのGUIツールからRDSに接続できることを確認

## セキュリティのベストプラクティス

### 1. 最小権限の原則

- セキュリティグループで、必要最小限のポートのみを開ける
- 自分のIPアドレスからの接続のみを許可（可能な場合）

### 2. SSM Session Managerの使用

- SSHキーの管理が不要
- 接続ログがCloudWatch Logsに記録される
- セキュリティグループでSSHポートを開ける必要がない

### 3. 定期的な更新

- AMIの定期的な更新
- セキュリティパッチの適用

### 4. アクセス制御

- IAMロールでSSM接続を制御
- CloudTrailでアクセスログを監視

## トラブルシューティング

### 踏み台サーバーに接続できない場合

1. **インスタンスの状態確認**
   - EC2コンソールでインスタンスが「実行中」であることを確認
   - ステータスチェックが成功していることを確認

2. **セキュリティグループの確認**
   - SSHポート（22）が開いているか確認（SSH接続の場合）
   - 自分のIPアドレスからの接続が許可されているか確認

3. **IAMロールの確認**
   - IAMインスタンスプロファイルが正しく設定されているか確認
   - `AmazonSSMManagedInstanceCore` ポリシーが付与されているか確認

4. **SSM Agentの確認**
   - Amazon Linux 2023/2にはSSM Agentがプリインストールされています
   - インスタンス起動後、数分待ってから接続を試みてください

### RDSに接続できない場合

1. **セキュリティグループの確認**
   - RDSのセキュリティグループで踏み台サーバーからの接続が許可されているか確認
   - ポート5432（PostgreSQL）が開いているか確認

2. **ネットワークの確認**
   - 踏み台サーバーとRDSが同じVPC内にあることを確認
   - ルートテーブルの設定を確認

3. **認証情報の確認**
   - RDSのマスターユーザー名とパスワードが正しいか確認
   - Secrets Managerから取得した認証情報を使用

### セキュリティグループの設定ミス

**よくある間違い**:
- 踏み台サーバーのセキュリティグループでRDSポートを開ける（不要）
- RDSのセキュリティグループで踏み台サーバーのセキュリティグループを許可しない

**正しい設定**:
- 踏み台サーバーのセキュリティグループ: SSH（22）のみ開ける（SSM使用時は不要）
- RDSのセキュリティグループ: 踏み台サーバーのセキュリティグループからの接続を許可

### ポートフォワーディングが動作しない場合

1. **ポートの競合確認**
   - ローカルで既にPostgreSQLが動いている場合、ポート5432は使用できません
   - 5433や5434など、別のポートを使用してください
   - 使用中のポートを確認:
     ```bash
     lsof -i :5433
     ```

2. **SSM Session Manager Pluginの確認**
   - Session Manager Pluginがインストールされているか確認
   - macOS: `brew install --cask session-manager-plugin`
   - Linux: [AWS公式ドキュメント](https://docs.aws.amazon.com/ja_jp/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html)を参照

3. **接続の維持**
   - ポートフォワーディングは接続を維持している間のみ有効です
   - 別のターミナルでGUIツールを起動してください
   - ポートフォワーディングのターミナルは閉じないでください

## コスト最適化

### 1. インスタンスタイプの選択

- 学習環境では `t3.micro` で十分
- 使用しない場合は停止または削除

### 2. Elastic IPの管理

- 使用していないElastic IPは削除（料金が発生します）
- インスタンスを停止する場合は、Elastic IPを解放

### 3. 自動停止の設定

- 使用しない時間帯に自動停止する設定を検討
- EventBridgeとLambdaを使用した自動停止スケジュール

## まとめ

### 作成したリソース

| リソース | 名前 | 用途 |
|---------|------|------|
| セキュリティグループ | `learning-bastion-sg-stg` | 踏み台サーバー用 |
| IAMロール | `learning-bastion-role-stg` | SSM接続用 |
| EC2インスタンス | `learning-bastion-stg` | 踏み台サーバー |
| Elastic IP | （オプション） | 固定IPアドレス |

### 重要なポイント

- **パブリックサブネットに配置**: インターネットからアクセス可能
- **SSM Session Manager推奨**: SSHキー不要、セキュア
- **最小権限の原則**: 必要最小限のポートのみを開ける
- **RDSセキュリティグループの更新**: 踏み台サーバーからの接続を許可

## 参考リンク

- [VPC設計ドキュメント](./1_vpc.md)
- [RDS設計ドキュメント](./2_rds.md)
- [ECS設定ドキュメント](./5_ecs-setup.md)
- [AWS Systems Manager Session Manager ドキュメント](https://docs.aws.amazon.com/ja_jp/systems-manager/latest/userguide/session-manager.html)
- [ECS Exec ドキュメント](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/ecs-exec.html)

