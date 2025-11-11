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

#### 1. NATインスタンス用のセキュリティグループを作成する

**注意**: ECSタスク用セキュリティグループ（`learning-app-sg-stg` など）が先に作成されている必要があります。

- [x] **NATインスタンス用セキュリティグループ作成**
  - EC2コンソール → セキュリティグループ → セキュリティグループを作成
  - セキュリティグループ名: `learning-nat-instance-sg-stg`
  - 説明: NATインスタンス用セキュリティグループ
  - VPC: `learning-stg`

- [x] **インバウンドルール設定**

  **推奨方法**: プライベートサブネットのCIDRブロックを指定

  | タイプ | プロトコル | ポート範囲 | ソース | 説明 |
  |--------|-----------|-----------|--------|------|
  | All traffic | All | All | 10.0.16.0/20 (private-subnet-nat-1a-stg) | プライベートサブネット1aから |
  | All traffic | All | All | 10.0.80.0/20 (private-subnet-nat-1c-stg) | プライベートサブネット1cから |

  **CIDRブロックを指定するメリット**:
  - プライベートサブネット内のすべてのリソースからのトラフィックを許可
  - 新しいリソース（ECSタスク、EC2インスタンスなど）を追加しても、セキュリティグループの変更が不要
  - NATインスタンスの本来の用途（プライベートサブネット全体のゲートウェイ）に合致
  - 管理がシンプルで柔軟性が高い

  **代替方法（セキュリティグループIDで指定）**:
  - より厳格なセキュリティが必要な場合
  - 特定のセキュリティグループを持つリソースのみに制限したい場合
  - 例: `sg-xxxxxx (learning-app-sg-stg)` - ECSタスクのセキュリティグループのみ許可

- [x] **アウトバウンドルール設定**
  | タイプ | プロトコル | ポート範囲 | 宛先 | 説明 |
  |--------|-----------|-----------|------|------|
  | All traffic | All | All | 0.0.0.0/0 | インターネットへ |

#### 2. NATインスタンス用のIAMロールを作成する

NATインスタンスをSSM（Systems Manager）経由で管理できるように、IAMロールとインスタンスプロファイルを作成します。

- [x] **IAMロール作成**
  - IAMコンソール → ロール → ロールを作成
  - ロール名: `learning-nat-instance-role-stg`
  - 信頼されたエンティティタイプ: AWS のサービス
  - ユースケース: EC2
  - 信頼ポリシー: EC2サービスを信頼（自動設定）

- [x] **ポリシーの付与**
  - 付与するポリシー:
    - `AmazonSSMManagedInstanceCore` - SSM経由でのインスタンス管理に必要
  - ロールを作成

- インスタンスプロファイルは自動的に作成される（EC2ロール作成時に自動生成）

#### 3. NATインスタンス用のEC2を作成する

- [x] **NATインスタンスを起動**
  - EC2コンソール → インスタンス → インスタンスを起動
  - 名前: `learning-nat-instance-stg`
  - AMI: Amazon Linux 2023またはAmazon Linux 2（最新版）
  - インスタンスタイプ: t3.micro

- [x] **ネットワーク設定**
  - VPC: `learning-stg`
  - サブネット: `public-subnet-1a-stg`
  - パブリックIPの自動割り当て: **有効化**
  - セキュリティグループ: `learning-nat-instance-sg-stg` を選択

- [x] **ストレージ設定**
  - ルートボリューム: 8GB GP3（最小推奨 デフォルト）

- [x] **詳細設定**
  - IAMインスタンスプロファイル: `learning-nat-instance-role-stg` を選択
  - これにより、SSM経由でインスタンスに接続可能になります（SSHキー不要）

- [x] **インスタンスを起動**
  - インスタンス状態が「実行中」になるまで待機
  - ステータスチェックが成功するまで待機
  - キーペア: **「Proceed without key pair」を選択**（SSM接続を使用するため不要）

**SSM接続のメリット**:
- SSHキーが不要
- セキュリティグループでSSHポート（22）を開ける必要がない
- AWS Systems Manager Session Manager経由でブラウザから接続可能

#### 4. EC2にIAMインスタンスプロフィールを設定する理由を理解する

**IAMインスタンスプロファイルとは**:
- EC2インスタンスがAWSサービスにアクセスするための認証情報を提供する仕組み
- IAMロールをEC2インスタンスにアタッチするためのコンテナ

**設定する理由**:

1. **SSM（Systems Manager）経由での接続**
   - `AmazonSSMManagedInstanceCore` ポリシーにより、SSM Session Manager経由でインスタンスに接続可能
   - SSHキーが不要で、セキュリティが向上
   - ブラウザから直接接続可能

2. **AWSサービスへのアクセス**
   - CloudWatch Logsへのログ送信
   - その他のAWSサービスへのアクセス（必要に応じて）

3. **セキュリティの向上**
   - 認証情報をインスタンス内に保存する必要がない
   - IAMロールの権限を動的に管理可能

**設定確認**:
- EC2コンソールでインスタンスを選択
- 「セキュリティ」タブ → IAMロール/インスタンスプロファイルを確認
- `learning-nat-instance-role-stg` がアタッチされていることを確認

#### 5. EC2でNATの設定を行う

NATインスタンスにSSM経由で接続し、NAT設定を実施します。

- [x] **SSM経由でインスタンスに接続**
  - EC2コンソールで `learning-nat-instance-stg` を選択
  - 「接続」→「Session Manager」タブ → 「接続」をクリック
  - ブラウザでターミナルが開く

- [x] **NAT設定コマンドの実行**

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
  
  # 設定の確認
  sudo iptables -t nat -L -n -v
  ```

- [x] **Source/Destination Checkの無効化**
  - EC2コンソールでNATインスタンスを選択
  - 「アクション」→「ネットワーキング」→「送信元/送信先チェックの変更」
  - 「停止」を選択して「保存」
  
  **理由**: NATインスタンスは他のインスタンスのトラフィックを転送するため、Source/Destination Checkを無効化する必要があります。

#### 6. NATインスタンスに静的IPを紐づける重要性と手順を理解する

**静的IP（Elastic IP）を紐づける重要性**:

1. **IPアドレスの固定**
   - インスタンス停止・起動時にパブリックIPが変わるのを防ぐ
   - ルートテーブルの設定が変更されない

2. **運用の安定性**
   - ルートテーブルでNATインスタンスのIPアドレスを指定している場合、IP変更により通信が途絶える
   - セキュリティグループやファイアウォールルールでIPアドレスを許可している場合、IP変更によりアクセスできなくなる

3. **コスト**
   - Elastic IPはインスタンスにアタッチされている限り無料
   - アタッチされていないElastic IPには料金が発生（$0.005/時間）

**手順**:

- [ ] **Elastic IPを割り当て**
  - EC2コンソール → Elastic IP → Elastic IPアドレスを割り当て
  - ネットワークボーダーグループ: なし（デフォルト）
  - パブリックIPv4アドレスプール: AmazonのIPv4アドレスプール
  - 「割り当て」をクリック

- [ ] **Elastic IPをNATインスタンスにアタッチ**
  - 割り当てたElastic IPを選択
  - 「アクション」→「Elastic IPアドレスの関連付け」
  - リソースタイプ: インスタンス
  - インスタンス: `learning-nat-instance-stg` を選択
  - プライベートIPアドレス: 自動選択
  - 「関連付け」をクリック

- [ ] **確認**
  - EC2コンソールでインスタンスを選択
  - 「ネットワーキング」タブでElastic IPが表示されていることを確認

#### 7. プライベートサブネットのルートをNATゲートウェイからNATインスタンスへ切り替える

プライベートサブネットのルートテーブルを更新して、NATインスタンス経由でインターネットにアクセスできるようにします。

- [x] **現在のルートテーブル設定を確認**
  - VPCコンソール → ルートテーブル → `learning-rtb-private-nat-stg` を選択
  - 現在のルート: `0.0.0.0/0` → `learning-nat-1a-stg` (NATゲートウェイ) を確認

- [x] **ルートテーブルを更新**
  - ルートテーブル: `learning-rtb-private-nat-stg` を選択
  - 「ルート」タブ → 「ルートを編集」
  - 既存の `0.0.0.0/0` ルートを削除
  - 「ルートを追加」をクリック
  - 新しいルートを追加:
    - 送信先: `0.0.0.0/0`
    - ターゲット: NATインスタンスのインスタンスID（`learning-nat-instance-stg`）を選択
  - 「変更を保存」をクリック

- [x] **動作確認**
  - プライベートサブネット内のEC2インスタンス（またはECSタスク）からインターネット接続をテスト
  - SSM経由でプライベートサブネット内のリソースに接続してテスト:
    ```bash
    # HTTP接続テスト
    curl http://www.example.com
    
    # HTTPS接続テスト
    curl https://www.google.com
    
    # DNS解決テスト
    nslookup www.amazon.com
    ```

- [ ] **既存NATゲートウェイの削除（オプション）**
  - 動作確認が完了したら、既存のNATゲートウェイを削除可能
  - VPCコンソール → NATゲートウェイ → `learning-nat-1a-stg` を選択
  - 「削除」を選択
  - 確認して削除
  - Elastic IPは自動的に解放される

## まとめ

### 構成概要
- **NATインスタンス**: プライベートサブネットからのアウトバウンド通信を提供
- **配置**: パブリックサブネット `public-subnet-1a-stg`
- **IPアドレス**: Elastic IPで固定（静的IP）

### 作成したリソース
| リソース | 名前 | 用途 |
|---------|------|------|
| IAMロール | learning-nat-instance-role-stg | NATインスタンス用IAMロール（SSM管理用） |
| セキュリティグループ | learning-nat-instance-sg-stg | NATインスタンス用セキュリティグループ |
| EC2インスタンス | learning-nat-instance-stg | NATインスタンス本体 |
| Elastic IP | （自動割り当て） | NATインスタンスの静的IPアドレス |

### 設定内容

**セキュリティグループ設定**:
- インバウンド: プライベートサブネット（10.0.16.0/20, 10.0.80.0/20）からのHTTP/HTTPSトラフィックを許可
- アウトバウンド: インターネットへの全トラフィックを許可

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
