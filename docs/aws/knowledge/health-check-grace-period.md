# Health Check Grace Period の設定ガイド

## 概要

ECSサービスの **Health Check Grace Period（ヘルスチェック猶予期間）** は、ALBヘルスチェックの失敗を無視する期間です。タスクの起動・初期化時間を考慮した猶予期間として設定します。

## Health Check Grace Period とは

**ALBヘルスチェックの失敗を無視する期間**
- この期間内はALBヘルスチェックが失敗してもタスクを異常とみなさない
- タスクの起動・初期化時間を考慮した猶予期間
- ECSサービスの作成時に設定する

## 推奨設定値

### 標準的なLaravelアプリケーション

**推奨値: 120秒**

### 環境別の推奨値

| 環境 | 推奨値 | 理由 |
|-----|--------|------|
| **開発環境** | 90秒 | キャッシュ最適化済み、高速起動 |
| **ステージング環境** | **120秒** | 本番同等の設定でテスト |
| **本番環境** | 120-180秒 | 安全マージンを大きく |
| **大規模本番環境** | 180秒 | データベース接続プール初期化等を考慮 |

## 計算根拠

### タスク起動からhealthyになるまでの時間

```
┌─────────────────────────────────────────────────┐
│ タスク起動からhealthyになるまでの時間           │
└─────────────────────────────────────────────────┘

1. ECSタスク起動                           0秒
   ├─ コンテナイメージプル                 5-15秒
   ├─ コンテナ起動                         3-5秒
   └─ Supervisor起動（Nginx + PHP-FPM）    2-5秒
                                          ↓ 合計 10-25秒

2. ECSタスクヘルスチェック開始期間         60秒
   ├─ Laravel初期化                       10-20秒
   ├─ データベース接続確立                 5-10秒
   └─ `/up` エンドポイントが応答可能に
                                          ↓ 合計 60-90秒

3. ALBヘルスチェック開始                   
   ├─ ターゲット登録                       5-10秒
   ├─ 初回ヘルスチェック待機（間隔30秒）    0-30秒
   ├─ healthy判定（連続2回成功）           30-60秒
   └─ ALBからのトラフィック開始
                                          ↓ 合計 35-100秒

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
合計: 105-215秒（最悪ケース）
平均: 約 120-150秒
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

推奨値: 120秒（平均的な起動時間をカバー）
```

### 詳細なタイムライン

```
時刻 0秒: タスク起動開始
    ↓
時刻 20秒: コンテナ起動完了
    ↓
時刻 60秒: ECSタスクヘルスチェック開始期間終了
    ├─ この時点で `/up` が応答可能
    └─ ECSタスクは healthy
    ↓
時刻 70秒: ALBにターゲット登録完了
    ↓
時刻 90秒: ALB初回ヘルスチェック実行
    └─ 成功 (200 OK)
    ↓
時刻 120秒: ALB 2回目のヘルスチェック実行
    ├─ 成功 (200 OK)
    └─ 連続2回成功で healthy 判定
    ↓
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
時刻 120秒: ヘルスチェック猶予期間終了
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    ↓
時刻 120秒以降: ALBヘルスチェックの結果を評価開始
    ├─ healthy → トラフィック受信開始
    └─ unhealthy → タスク再起動
```

## ECSタスクヘルスチェックとの関係

### 2つのヘルスチェックの違い

| 項目 | ECSタスクヘルスチェック | ALBヘルスチェック |
|-----|----------------------|------------------|
| **実行場所** | コンテナ内部 | ALBから |
| **開始期間** | 60秒 | 120秒（猶予期間） |
| **間隔** | 30秒 | 30秒 |
| **タイムアウト** | 5秒 | 5秒 |
| **healthy判定** | 即座 | 連続2回成功 |
| **用途** | コンテナの健全性 | トラフィック振り分け |

### 連携した動作

```
┌─────────────────────────────────────────┐
│ ECSタスクヘルスチェック（開始期間: 60秒）│
└─────────────────────────────────────────┘
     0秒          60秒         90秒       120秒
     │────猶予────│──評価──│──評価──│
     │            ✓ healthy ✓ healthy ✓
     
┌─────────────────────────────────────────┐
│ ALBヘルスチェック（猶予期間: 120秒）    │
└─────────────────────────────────────────┘
     0秒                      90秒       120秒
     │────────猶予期間────────│──評価──│
     │                        ✓ check  ✓ healthy
     
→ 120秒時点で両方が healthy なら正常動作開始
```

## 設定が短すぎる場合の問題

### 猶予期間: 60秒 の場合

```
時刻 0秒: タスク起動
    ↓
時刻 60秒: 猶予期間終了
    ├─ まだALBヘルスチェックが healthy になっていない
    └─ unhealthy と判定される可能性
    ↓
❌ タスクが不必要に再起動される
   （実際には正常に起動できるのに）
```

**問題点**:
- 正常なタスクが誤って再起動される
- デプロイが失敗と判定される
- サービスが不安定になる

## 設定が長すぎる場合の問題

### 猶予期間: 300秒（5分）の場合

```
時刻 0秒: タスク起動
    ↓
時刻 120秒: 実際には起動失敗している
    ↓
時刻 300秒まで: 失敗を検知できない
    ↓
❌ デプロイの失敗検知が遅れる
❌ ダウンタイムが長くなる
```

**問題点**:
- 異常なタスクの検知が遅れる
- デプロイ失敗の検知が遅れる
- ユーザーへの影響時間が長くなる

## ベストプラクティス

### 推奨設定

```yaml
# ECSタスク定義のヘルスチェック
Health Check:
  Start Period: 60秒

# ALBターゲットグループのヘルスチェック
Health Check:
  Interval: 30秒
  Healthy Threshold: 2回
  Unhealthy Threshold: 2回

# ECSサービスのヘルスチェック猶予期間
Service:
  Health Check Grace Period: 120秒
```

### 計算式

```
Health Check Grace Period = 
  ECSタスク開始期間(60秒) + 
  ALB healthy判定時間(60秒) + 
  バッファ(0秒)
  
= 120秒
```

### ALBヘルスチェック判定時間の計算

```
ALB healthy判定時間 = 
  ターゲット登録時間(5-10秒) +
  初回ヘルスチェック待機(0-30秒) +
  連続2回成功までの時間(30-60秒)
  
= 35-100秒

→ 安全マージンを含めて 60秒 と見積もる
```

## 調整が必要なケース

### より長くする場合（150-180秒）

以下の場合は **150-180秒** を検討:

- **データベース接続プールが大きい**
  - 初期接続確立に時間がかかる
  - 複数のデータベースに接続する

- **初回リクエスト時のキャッシュウォームアップが重い**
  - 設定ファイルのキャッシュ生成
  - ルートキャッシュの生成

- **外部APIとの接続確立が必要**
  - 認証トークンの取得
  - 接続プールの初期化

- **マイグレーション実行が含まれる**
  - デプロイ時にマイグレーション実行
  - データベーススキーマの更新

### より短くする場合（90秒）

以下の場合は **90秒** も可能:

- **すべてのキャッシュが最適化済み**
  - Config キャッシュ
  - Route キャッシュ
  - View キャッシュ

- **データベース接続が高速**
  - RDS Proxy 使用
  - 接続プール最適化済み

- **軽量なコンテナイメージ**
  - 最小限の依存関係
  - マルチステージビルド最適化

- **十分なテストで起動時間が把握できている**
  - 実際の起動時間が60秒以内
  - 安定して起動できる

## 実際の設定例

### ECSサービス作成時の設定

```yaml
# AWS CLI での設定例
aws ecs create-service \
  --cluster learning-cluster-stg \
  --service-name learning-ecs-service-stg \
  --task-definition learning-app-task-stg:latest \
  --desired-count 1 \
  --launch-type FARGATE \
  --network-configuration "awsvpcConfiguration={subnets=[subnet-xxx,subnet-yyy],securityGroups=[sg-xxx],assignPublicIp=DISABLED}" \
  --load-balancers "targetGroupArn=arn:aws:elasticloadbalancing:ap-northeast-1:xxx:targetgroup/learning-tg-stg/xxx,containerName=app,containerPort=80" \
  --health-check-grace-period-seconds 120
```

### Terraform での設定例

```hcl
resource "aws_ecs_service" "learning_service" {
  name            = "learning-ecs-service-stg"
  cluster         = aws_ecs_cluster.learning_cluster.id
  task_definition = aws_ecs_task_definition.learning_task.arn
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = [aws_subnet.private_1a.id, aws_subnet.private_1c.id]
    security_groups  = [aws_security_group.ecs_task.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.learning_tg.arn
    container_name   = "app"
    container_port   = 80
  }

  health_check_grace_period_seconds = 120

  deployment_configuration {
    maximum_percent         = 200
    minimum_healthy_percent = 100
  }

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }
}
```

## モニタリングと調整

### CloudWatch メトリクスで確認

**確認すべきメトリクス**:
- `ServiceDesiredTaskCount`: 希望タスク数
- `ServiceRunningTaskCount`: 実行中のタスク数
- `ServiceHealthyTaskCount`: 正常なタスク数

### ログで確認

**CloudWatch Logs で確認**:
```bash
# ECSサービスのイベントログ
aws ecs describe-services \
  --cluster learning-cluster-stg \
  --services learning-ecs-service-stg \
  --query 'services[0].events[:5]'
```

**確認ポイント**:
- タスクが起動してからhealthyになるまでの時間
- デプロイ時のタスク再起動の有無
- ヘルスチェック失敗の頻度

### 調整の判断基準

**猶予期間を延長すべき場合**:
- デプロイ時にタスクが頻繁に再起動される
- CloudWatch Logsで起動に時間がかかっている
- ヘルスチェック失敗のログが多い

**猶予期間を短縮できる場合**:
- 実際の起動時間が60秒以内で安定している
- デプロイが常に成功している
- ヘルスチェックが早期に成功している

## トラブルシューティング

### 問題: タスクが起動直後に再起動される

**原因**: 猶予期間が短すぎる

**解決策**:
```yaml
# 猶予期間を延長
Health Check Grace Period: 150秒 または 180秒
```

### 問題: デプロイ失敗の検知が遅い

**原因**: 猶予期間が長すぎる

**解決策**:
```yaml
# 猶予期間を短縮（ただし慎重に）
Health Check Grace Period: 90秒 または 120秒

# まずは実際の起動時間を測定
# CloudWatch Logsでタスク起動からhealthyまでの時間を確認
```

### 問題: デプロイは成功するが、起動に時間がかかる

**原因**: アプリケーションの初期化が重い

**解決策**:
1. **猶予期間を延長**（一時的な対策）
   ```yaml
   Health Check Grace Period: 180秒
   ```

2. **アプリケーションの最適化**（根本的な対策）
   - Config キャッシュの生成
   - Route キャッシュの生成
   - データベース接続の最適化

## まとめ

### 推奨値: 120秒

**理由**:
1. ✅ ECSタスクの起動時間（60秒）を十分にカバー
2. ✅ ALBヘルスチェックのhealthy判定時間（30-60秒）を考慮
3. ✅ 誤ったタスク再起動を防止
4. ✅ デプロイ失敗の迅速な検知も可能
5. ✅ AWS のベストプラクティスに準拠

### 設定のポイント

- **標準的なLaravelアプリケーション**: 120秒
- **軽量なアプリケーション**: 90秒
- **重いアプリケーション**: 150-180秒
- **実際の起動時間を測定して調整**: 最適化の基本

この設定により、正常なタスクが誤って再起動されることを防ぎつつ、異常なタスクは適切に検知・再起動されます。

## 参考リンク

- [ECSサービス設定ドキュメント](../infrastructure-setup/7_ecs-service.md)
- [ECSタスク定義ドキュメント](../infrastructure-setup/6_ecs-task-definition.md)
- [APP_URLとヘルスチェックの違い](./app-url-and-health-check.md)
- [AWS ECS サービスのヘルスチェック](https://docs.aws.amazon.com/ja_jp/AmazonECS/latest/developerguide/service-health-checks.html)

