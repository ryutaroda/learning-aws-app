# NATインスタンス作成手順書

## 作成順序

### 前提条件
1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **パブリックサブネット作成完了**: `public-subnet-1a-stg`
3. ✅ **プライベートサブネット作成完了**: `private-subnet-nat-1a-stg`, `private-subnet-nat-1c-stg`
4. ✅ **インターネットゲートウェイ作成完了**: `learning-igw-stg`
5. ✅ **ルートテーブル作成完了**: `learning-rtb-private-nat-stg`

### 作成手順

**注意**: 以下の手順はすべてAWSマネジメントコンソール上で実施します。

#### 1. NAT AMIの作成

NATインスタンス用のAMIを作成します。Amazon Linux 2023またはAmazon Linux 2を使用します。

- [ ] **一時的なEC2インスタンスを起動**
  - AMI: Amazon Linux 2023またはAmazon Linux 2
  - インスタンスタイプ: t3.micro（最小で可）
  - サブネット: `public-subnet-1a-stg`
  - セキュリティグループ: SSHアクセス可能なもの（一時的）

- [ ] **NAT設定コマンドの実行**

  ```bash
  # iptablesサービスのインストールと有効化
  sudo yum install iptables-services -y
  sudo systemctl enable iptables
  sudo systemctl start iptables
  
  # IP転送の有効化（再起動後も永続化）
  echo "net.ipv4.ip_forward=1" | sudo tee /etc/sysctl.d/custom-ip-forwarding.conf
  sudo sysctl -p /etc/sysctl.d/custom-ip-forwarding.conf
  
  # プライマリネットワークインターフェースの確認
  PRIMARY_INTERFACE=$(ip route | grep default | awk '{print $5}' | head -1)
  echo "プライマリインターフェース: ${PRIMARY_INTERFACE}"
  
  # NAT設定（iptables MASQUERADEルール）
  sudo /sbin/iptables -t nat -A POSTROUTING -o ${PRIMARY_INTERFACE} -j MASQUERADE
  sudo /sbin/iptables -F FORWARD
  sudo service iptables save
  ```

- [ ] **AMIを作成**
  - EC2コンソールでインスタンスを選択
  - 「アクション」→「イメージとテンプレート」→「イメージを作成」
  - イメージ名: `learning-nat-ami-stg`
  - 説明: NATインスタンス用AMI

- [ ] **一時インスタンスを終了・削除**

#### 2. セキュリティグループ作成

**注意**: ECSタスク用セキュリティグループ（`learning-ecs-task-sg-stg` など）が先に作成されている必要があります。

- [ ] **NATインスタンス用セキュリティグループ作成**
  - セキュリティグループ名: `learning-nat-instance-sg-stg`
  - VPC: `learning-stg`
  - 説明: NATインスタンス用セキュリティグループ

- [ ] **インバウンドルール設定**

  **推奨方法**: セキュリティグループIDで指定（よりセキュア）

  | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
  |--------|-----------|-----------|--------|------|
  | HTTP | TCP | 80 | sg-xxxxxx (learning-ecs-task-sg-stg) | ECSタスクからのHTTP |
  | HTTPS | TCP | 443 | sg-xxxxxx (learning-ecs-task-sg-stg) | ECSタスクからのHTTPS |

  **セキュリティグループIDで指定するメリット**:
  - 特定のセキュリティグループを持つリソースのみに制限できる
  - サブネット内の全リソースを許可するのではなく、必要なリソースのみに絞れる
  - 管理しやすく、より柔軟な制御が可能

- [ ] **アウトバウンドルール設定**
  | タイプ | プロトコル | ポート範囲 | 宛先 | 説明 |
  |--------|-----------|-----------|------|------|
  | HTTP | TCP | 80 | 0.0.0.0/0 | インターネットへのHTTP |
  | HTTPS | TCP | 443 | 0.0.0.0/0 | インターネットへのHTTPS |
  | カスタムTCP | TCP | 53 | 0.0.0.0/0 | DNS（必要に応じて） |
  | カスタムUDP | UDP | 53 | 0.0.0.0/0 | DNS（必要に応じて） |

#### 3. IAMロール作成

NATインスタンスをSSM（Systems Manager）経由で管理できるように、IAMロールとインスタンスプロファイルを作成します。

- [ ] **IAMロール作成**
  - ロール名: `learning-nat-instance-role-stg`
  - 信頼ポリシー: EC2サービスを信頼
  - 付与するポリシー:
    - `AmazonSSMManagedInstanceCore` - SSM経由でのインスタンス管理に必要

- [ ] **インスタンスプロファイル作成**
  - インスタンスプロファイル名: `learning-nat-instance-profile-stg`
  - 上記で作成したIAMロールをアタッチ

**IAMロールの信頼ポリシー例**:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Service": "ec2.amazonaws.com"
      },
      "Action": "sts:AssumeRole"
    }
  ]
}
```

#### 4. EC2インスタンス作成

- [ ] **NATインスタンスを起動**
  - 名前: `learning-nat-instance-stg`
  - AMI: 作成したNAT AMI（`learning-nat-ami-stg`）
  - インスタンスタイプ: t3.small（トラフィック量に応じて調整）
  - キーペア: 既存のキーペアを選択

- [ ] **ネットワーク設定**
  - VPC: `learning-stg`
  - サブネット: `public-subnet-1a-stg`
  - パブリックIPの自動割り当て: **有効化**
  - セキュリティグループ: `learning-nat-instance-sg-stg`

- [ ] **ストレージ設定**
  - ルートボリューム: 8GB GP3（最小推奨）

- [ ] **IAMロールの設定**
  - IAMインスタンスプロファイル: `learning-nat-instance-profile-stg` を選択
  - これにより、SSM経由でインスタンスに接続可能になります（SSHキー不要）

- [ ] **インスタンスを起動**

**注意**: Elastic IPは必須ではありません。パブリックIPの自動割り当てで動作します。IPアドレスを固定したい場合は後からElastic IPを割り当て可能です。

  - インスタンス状態が「実行中」になるまで待機
  - ステータスチェックが成功するまで待機

**SSM接続のメリット**:
- SSHキーが不要
- セキュリティグループでSSHポート（22）を開ける必要がない
- AWS Systems Manager Session Manager経由でブラウザから接続可能

#### 5. Source/Destination Checkの無効化

NATインスタンスは他のインスタンスのトラフィックを転送するため、Source/Destination Checkを無効化する必要があります。

- [ ] **Source/Destination Checkを無効化**
  - EC2コンソールでNATインスタンスを選択
  - 「アクション」→「ネットワーキング」→「送信元/送信先チェックの変更」
  - 「停止」を選択して「保存」

#### 6. ルートテーブルの更新

プライベートサブネットのルートテーブルを更新して、NATインスタンス経由でインターネットにアクセスできるようにします。

- [ ] **ルートテーブルを更新**
  - ルートテーブル: `learning-rtb-private-nat-stg`
  - 既存のルート: `0.0.0.0/0` → `learning-nat-1a-stg` (NATゲートウェイ)
  - ルートを編集:
    - 既存の `0.0.0.0/0` ルートを削除
    - 新しいルートを追加:
      - 送信先: `0.0.0.0/0`
      - ターゲット: NATインスタンスのインスタンスID（`learning-nat-instance-stg`）

#### 7. 動作確認

- [ ] **プライベートサブネットからのインターネット接続テスト**

  ```bash
  # HTTP接続テスト
  curl http://www.example.com
    
  # HTTPS接続テスト
  curl https://www.google.com
    
  # DNS解決テスト
  nslookup www.amazon.com
  ```

- [ ] **CloudWatchメトリクスで確認**
  - EC2コンソールでNATインスタンスのメトリクスを確認
  - CPU使用率、ネットワークイン/アウトを確認

#### 8. 既存NATゲートウェイの削除（オプション）

- [ ] **NATゲートウェイを削除**
  - VPCコンソールで `learning-nat-1a-stg` を選択
  - 「削除」を選択
  - 確認して削除
  - Elastic IPは自動的に解放される

## まとめ

### 構成概要
- **NATインスタンス**: プライベートサブネットからのアウトバウンド通信を提供
- **配置**: パブリックサブネット `public-subnet-1a-stg`
- **IPアドレス**: パブリックIP自動割り当て（Elastic IPはオプション）

### 作成したリソース
| リソース | 名前 | 用途 |
|---------|------|------|
| AMI | learning-nat-ami-stg | NATインスタンス用AMI |
| IAMロール | learning-nat-instance-role-stg | NATインスタンス用IAMロール（SSM管理用） |
| インスタンスプロファイル | learning-nat-instance-profile-stg | NATインスタンス用インスタンスプロファイル |
| セキュリティグループ | learning-nat-instance-sg-stg | NATインスタンス用セキュリティグループ |
| EC2インスタンス | learning-nat-instance-stg | NATインスタンス本体 |

### 設定内容

**セキュリティグループ設定**:
- インバウンド: プライベートサブネットからのHTTP/HTTPSトラフィックを許可
- アウトバウンド: インターネットへのHTTP/HTTPS/DNSトラフィックを許可

**ルートテーブル設定**:
- `learning-rtb-private-nat-stg`: `0.0.0.0/0` → NATインスタンス

### 注意事項

- **単一障害点**: NATインスタンスは単一障害点となる可能性があるため、本番環境では冗長化を検討
- **メンテナンス**: NATインスタンスのOS更新やパッチ適用は手動で行う必要がある
- **インスタンスタイプ**: トラフィック量に応じてインスタンスタイプを調整（t3.small → t3.medium等）
- **監視**: CloudWatchアラームを設定してNATインスタンスの状態を監視することを推奨
- **IPアドレス**: インスタンス停止・起動時にパブリックIPが変わる可能性がある（Elastic IPを使用する場合は固定）

### コスト削減効果

- **NATゲートウェイ**: 約$0.045/時間 + データ転送料金
- **NATインスタンス**: t3.smallで約$0.0208/時間 + データ転送料金（インスタンス料金のみ）
- **削減率**: 約54%のコスト削減（インスタンス料金のみの場合）

### トラブルシューティング

**インターネット接続できない場合**:
1. NATインスタンスのステータスチェックが成功しているか確認
2. Source/Destination Checkが無効化されているか確認
3. セキュリティグループのルールが正しく設定されているか確認
4. ルートテーブルが正しく更新されているか確認
5. NATインスタンス自体がインターネットに接続できるか確認（SSHで接続してテスト）

**パフォーマンスが低い場合**:
- インスタンスタイプをアップグレード（t3.small → t3.medium等）
- CloudWatchメトリクスでCPU/ネットワーク使用率を確認
