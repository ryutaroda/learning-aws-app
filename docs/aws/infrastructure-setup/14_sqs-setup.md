# Amazon SQSキュー設定ドキュメント

## 概要

Laravelアプリケーションで使用するAmazon SQSキューを作成し、設定します。SQSを使用することで、スケーラブルで信頼性の高い非同期ジョブ処理が可能になります。

## 作成順序

### 前提条件

1. ✅ **VPC作成完了**: `learning-stg`
2. ✅ **IAMロール作成完了**: タスクロール（`learning-ecs-task-role-stg`）

### 作成手順

#### 1. SQSキューの作成

- [ ] **標準キューを作成**
  - キュー名: `learning-app-queue-stg`
  - キュータイプ: 標準キュー（Standard Queue）
  - リージョン: `ap-northeast-1`

#### 2. デッドレターキュー（DLQ）の作成（推奨）

- [ ] **DLQを作成**
  - キュー名: `learning-app-queue-dlq-stg`
  - 用途: 処理に失敗したジョブを保存

#### 3. IAMロールへの権限追加

- [ ] **タスクロールにSQS権限を追加**
  - ロール名: `learning-ecs-task-role-stg`
  - 必要な権限: SQSへの送信・受信・削除権限

## AWSコンソールでの作成手順

### ステップ1: 標準キューの作成

1. **AWSコンソールにログイン**
   - SQSサービスを開く
   - リージョン: `ap-northeast-1`を選択

2. **キューを作成**
   - 「キューを作成」ボタンをクリック
   - **名前**: `learning-app-queue-stg`
   - **キュータイプ**: 標準キュー（Standard Queue）を選択
   - **設定**: デフォルト設定で作成（後で調整可能）

3. **設定の確認**
   - **可視性タイムアウト**: 30秒（デフォルト）
   - **メッセージ保持期間**: 4日（デフォルト）
   - **配信遅延**: 0秒（デフォルト）
   - **最大受信数**: 3回（デフォルト）

### ステップ2: デッドレターキュー（DLQ）の作成

1. **DLQを作成**
   - 「キューを作成」ボタンをクリック
   - **名前**: `learning-app-queue-dlq-stg`
   - **キュータイプ**: 標準キュー（Standard Queue）を選択
   - **設定**: デフォルト設定で作成

2. **DLQの設定をメインキューに適用**
   - `learning-app-queue-stg`を選択
   - 「編集」をクリック
   - 「デッドレターキュー」セクションで以下を設定:
     - **デッドレターキュー**: `learning-app-queue-dlq-stg`を選択
     - **最大受信数**: 3（3回失敗したらDLQに移動）

### ステップ3: キューのURLを確認

作成後、以下の情報をメモしてください：

- **キューURL**: `https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-stg`
- **キューARN**: `arn:aws:sqs:ap-northeast-1:{ACCOUNT_ID}:learning-app-queue-stg`

## AWS CLIでの作成手順

### ステップ1: 標準キューの作成

```bash
# 標準キューを作成
aws sqs create-queue \
  --queue-name learning-app-queue-stg \
  --region ap-northeast-1 \
  --attributes '{
    "VisibilityTimeout": "30",
    "MessageRetentionPeriod": "345600",
    "ReceiveMessageWaitTimeSeconds": "0"
  }'

# キューURLを取得
aws sqs get-queue-url \
  --queue-name learning-app-queue-stg \
  --region ap-northeast-1
```

### ステップ2: デッドレターキュー（DLQ）の作成

```bash
# DLQを作成
aws sqs create-queue \
  --queue-name learning-app-queue-dlq-stg \
  --region ap-northeast-1

# DLQのARNを取得
aws sqs get-queue-attributes \
  --queue-url https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-dlq-stg \
  --attribute-names QueueArn \
  --region ap-northeast-1
```

### ステップ3: メインキューにDLQを設定

```bash
# DLQのARNを取得（上記コマンドの出力から取得）
DLQ_ARN="arn:aws:sqs:ap-northeast-1:{ACCOUNT_ID}:learning-app-queue-dlq-stg"

# メインキューにDLQを設定
aws sqs set-queue-attributes \
  --queue-url https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-stg \
  --attributes "{\"RedrivePolicy\":\"{\\\"deadLetterTargetArn\\\":\\\"${DLQ_ARN}\\\",\\\"maxReceiveCount\\\":3}\"}" \
  --region ap-northeast-1
```

## IAMロールへの権限追加

### タスクロールにSQS権限を追加

ECSタスクロール（`learning-ecs-task-role-stg`）にSQSへのアクセス権限を追加します。

#### 方法1: マネージドポリシーを使用（推奨）

1. **IAMコンソールを開く**
2. **ロールを選択**: `learning-ecs-task-role-stg`
3. **権限を追加**:
   - 「権限を追加」をクリック
   - 「ポリシーをアタッチ」を選択
   - `AmazonSQSFullAccess`を検索してアタッチ

#### 方法2: 最小権限のカスタムポリシーを作成

よりセキュアな方法として、必要な権限のみを付与するカスタムポリシーを作成：

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "sqs:SendMessage",
        "sqs:ReceiveMessage",
        "sqs:DeleteMessage",
        "sqs:GetQueueAttributes",
        "sqs:GetQueueUrl"
      ],
      "Resource": [
        "arn:aws:sqs:ap-northeast-1:{ACCOUNT_ID}:learning-app-queue-stg",
        "arn:aws:sqs:ap-northeast-1:{ACCOUNT_ID}:learning-app-queue-dlq-stg"
      ]
    }
  ]
}
```

**ポリシー名**: `learning-sqs-access-stg`

## Laravel設定

### 環境変数の設定

ECSタスク定義の環境変数に以下を追加：

```env
QUEUE_CONNECTION=sqs
AWS_DEFAULT_REGION=ap-northeast-1
SQS_QUEUE=learning-app-queue-stg
```

**注意**: 
- `AWS_ACCESS_KEY_ID`と`AWS_SECRET_ACCESS_KEY`はIAMロールを使用する場合は不要
- IAMタスクロールが正しく設定されていれば、自動的に認証されます

### config/queue.phpの確認

`config/queue.php`の`sqs`設定が正しいか確認：

```php
'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => env('SQS_PREFIX', 'https://sqs.ap-northeast-1.amazonaws.com/your-account-id'),
    'queue' => env('SQS_QUEUE', 'default'),
    'suffix' => env('SQS_SUFFIX'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'after_commit' => false,
],
```

## 動作確認

### ステップ1: キューの状態確認

```bash
# キューの属性を確認
aws sqs get-queue-attributes \
  --queue-url https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-stg \
  --attribute-names All \
  --region ap-northeast-1

# キューのメッセージ数を確認
aws sqs get-queue-attributes \
  --queue-url https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-stg \
  --attribute-names ApproximateNumberOfMessages ApproximateNumberOfMessagesNotVisible \
  --region ap-northeast-1
```

### ステップ2: テストメッセージの送信

```bash
# テストメッセージを送信
aws sqs send-message \
  --queue-url https://sqs.ap-northeast-1.amazonaws.com/{ACCOUNT_ID}/learning-app-queue-stg \
  --message-body '{"test": "message"}' \
  --region ap-northeast-1
```

### ステップ3: Laravelからの動作確認

1. **ECSタスク定義を更新**して環境変数を設定
2. **キューワーカーを起動**:
   ```bash
   php artisan queue:work sqs
   ```
3. **テストジョブをディスパッチ**:
   ```bash
   # Tinkerから
   php artisan tinker
   >>> use App\Jobs\TestJob;
   >>> TestJob::dispatch('テストメッセージ');
   ```
4. **CloudWatch Logsで確認**:
   - キューワーカーのログを確認
   - Jobが正常に処理されたことを確認

## キューの設定推奨値

### 標準キューの推奨設定

| 設定項目 | 推奨値 | 説明 |
|---------|--------|------|
| **可視性タイムアウト** | 30秒 | メッセージが処理中に見えなくなる時間 |
| **メッセージ保持期間** | 4日（345600秒） | 未処理メッセージの保持期間 |
| **配信遅延** | 0秒 | メッセージ配信の遅延時間 |
| **最大受信数** | 3回 | DLQに移動するまでの最大受信回数 |
| **受信メッセージ待機時間** | 0秒（短いポーリング） | ロングポーリングを使用する場合は20秒推奨 |

### デッドレターキュー（DLQ）の設定

| 設定項目 | 推奨値 | 説明 |
|---------|--------|------|
| **メッセージ保持期間** | 14日（1209600秒） | 失敗したジョブの保持期間（長めに設定） |
| **可視性タイムアウト** | 30秒 | デフォルト値で問題なし |

## コスト最適化

### 短いポーリング vs ロングポーリング

- **短いポーリング（0秒）**: 
  - 即座にレスポンス（空の場合は空のレスポンス）
  - リクエスト数が増える（コスト増）
  
- **ロングポーリング（20秒）**:
  - 最大20秒待機してメッセージを確認
  - リクエスト数が減る（コスト削減）
  - Laravelの`queue:work`コマンドで`--sleep`オプションと組み合わせて使用

**推奨**: ロングポーリングを使用する場合は、`config/queue.php`で設定：

```php
'sqs' => [
    // ... 他の設定
    'options' => [
        'ReceiveMessageWaitTimeSeconds' => 20, // ロングポーリング
    ],
],
```

## トラブルシューティング

### エラー1: Access Denied (403 Forbidden)

**原因**: IAMロールにSQSへのアクセス権限がない

**解決方法**:
1. IAMコンソールでタスクロールを確認
2. `AmazonSQSFullAccess`またはカスタムポリシーをアタッチ
3. ECSタスクを再起動

### エラー2: Queue does not exist

**原因**: キュー名が間違っている、またはリージョンが異なる

**解決方法**:
1. キュー名を確認: `SQS_QUEUE=learning-app-queue-stg`
2. リージョンを確認: `AWS_DEFAULT_REGION=ap-northeast-1`
3. キューが存在するか確認:
   ```bash
   aws sqs get-queue-url --queue-name learning-app-queue-stg --region ap-northeast-1
   ```

### エラー3: メッセージが処理されない

**原因**: 可視性タイムアウトが短すぎる、またはワーカーが起動していない

**解決方法**:
1. 可視性タイムアウトを確認（Jobの実行時間より長く設定）
2. キューワーカーが起動しているか確認
3. CloudWatch Logsでエラーを確認

### エラー4: DLQにメッセージが移動しない

**原因**: DLQの設定が正しくない

**解決方法**:
1. メインキューの`RedrivePolicy`を確認
2. DLQのARNが正しいか確認
3. `maxReceiveCount`が適切に設定されているか確認

## まとめ

### 作成したリソース

| リソース | 名前 | 用途 |
|---------|------|------|
| SQSキュー | `learning-app-queue-stg` | メインキュー（ジョブ処理用） |
| SQSキュー | `learning-app-queue-dlq-stg` | デッドレターキュー（失敗ジョブ用） |
| IAMポリシー | `learning-sqs-access-stg`（カスタムの場合） | SQSへのアクセス権限 |

### 重要なポイント

- **標準キューを使用**: 順序保証は不要なため、標準キューで十分
- **DLQを設定**: 失敗したジョブを保存してデバッグ可能にする
- **IAMロールで認証**: アクセスキーではなくIAMロールを使用
- **ロングポーリング**: コスト削減のため検討
- **可視性タイムアウト**: Jobの実行時間より長く設定

## 参考リンク

- [Amazon SQS ドキュメント](https://docs.aws.amazon.com/ja_jp/sqs/)
- [Laravel Queue SQS ドキュメント](https://laravel.com/docs/queues#sqs)
- [ECSキューワーカータスク設定](./15_queue-worker-task.md)
- [ECSタスク定義ドキュメント](./6_ecs-task-definition.md)

