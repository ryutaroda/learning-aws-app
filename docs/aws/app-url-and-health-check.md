# APP_URL とヘルスチェックの違い

## 概要

Laravel アプリケーションの `APP_URL` 環境変数と、ECS タスクのヘルスチェックで使用する URL の違いについて説明します。

## 重要なポイント

- **APP_URL**: ユーザーがブラウザからアクセスする外部 URL（CloudFront のドメイン）
- **ヘルスチェック**: コンテナ自身が自分自身の健全性を確認するための内部 URL（localhost）

## 通信フローと設定の関係

```
┌─────────────────────────────────────────────────────┐
│ ユーザーのブラウザ                                      │
│ URL: https://www.your-domain.com/products           │
│      ↑ APP_URL で設定する値                          │
└─────────────────────────────────────────────────────┘
                     ↓ HTTPS
┌─────────────────────────────────────────────────────┐
│ CloudFront                                           │
│ Domain: d111111abcdef8.cloudfront.net               │
└─────────────────────────────────────────────────────┘
                     ↓ HTTPS
┌─────────────────────────────────────────────────────┐
│ ALB                                                  │
│ learning-alb-stg-xxxxx.elb.amazonaws.com            │
│ ↑ CloudFront の Origin として設定                    │
└─────────────────────────────────────────────────────┘
                     ↓ HTTP (VPC内通信)
┌─────────────────────────────────────────────────────┐
│ ECS タスク (Nginx:80)                                │
│ APP_URL で設定された値を使って URL を生成              │
│                                                      │
│ ヘルスチェック:                                       │
│ wget http://localhost/up                            │
│ ↑ コンテナ自身が自分自身をチェック                     │
└─────────────────────────────────────────────────────┘
```

## APP_URL の役割

### 設定すべき値

```env
# ✅ 正しい設定（ユーザーがアクセスする URL）
APP_URL=https://www.your-domain.com
# または
APP_URL=https://d111111abcdef8.cloudfront.net

# ❌ 間違った設定
APP_URL=http://localhost/
APP_URL=https://learning-alb-stg-xxxxx.ap-northeast-1.elb.amazonaws.com
```

### APP_URL が使われる場面

1. **メール内のリンク生成**
   ```php
   // パスワードリセットメール
   Mail::send(new ResetPassword($token));
   
   // 生成されるURL
   // APP_URL=http://localhost なら
   // → http://localhost/reset-password/token123
   //    ↑ ユーザーはこれをクリックできない
   
   // APP_URL=https://www.your-domain.com なら
   // → https://www.your-domain.com/reset-password/token123
   //    ↑ ユーザーがアクセスできる
   ```

2. **API レスポンスの URL 生成**
   ```json
   {
     "data": {
       "id": 1,
       "image_url": "https://www.your-domain.com/storage/images/photo.jpg"
     }
   }
   ```

3. **アセット URL の生成**
   ```blade
   <!-- Blade テンプレート -->
   <img src="{{ asset('images/logo.png') }}">
   
   <!-- 生成される HTML -->
   <img src="https://www.your-domain.com/images/logo.png">
   ```

4. **セッション・Cookie のドメイン設定**
   - セッションクッキーのドメインが APP_URL に基づいて設定される
   - 実際のドメイン（CloudFront）と一致しないと認証が維持されない

### APP_URL が localhost だと起こる問題

- ❌ メール内のリンクがクリックできない
- ❌ API レスポンスの URL が外部からアクセスできない
- ❌ アセット（画像など）が読み込めない
- ❌ セッション・Cookie が正しく動作しない

## ヘルスチェックの役割

### ECS タスク定義での設定例

```yaml
Health Check:
  Command: CMD-SHELL, wget --no-verbose --tries=1 --spider http://localhost/up || exit 1
  Interval: 30秒
  Timeout: 5秒
  Retries: 3
  Start Period: 60秒
```

### なぜ localhost で良いのか

**実行場所の違い:**

| 用途 | 実行場所 | アクセス元 | URL |
|-----|---------|-----------|-----|
| **APP_URL** | Laravel アプリケーション | 外部（ユーザーのブラウザ） | `https://www.your-domain.com` |
| **ヘルスチェック** | ECS コンテナ内部 | コンテナ自身 | `http://localhost` |

### ヘルスチェックの実行コンテキスト

```bash
# ECS タスク定義のヘルスチェック
CMD-SHELL, wget --no-verbose --tries=1 --spider http://localhost/up || exit 1
```

**このコマンドが実行される場所:**
```
┌─────────────────────────────────────┐
│ ECS タスク (コンテナ)                │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ wget コマンド実行            │   │
│  │   ↓                         │   │
│  │ http://localhost:80/up      │   │
│  │   ↓                         │   │
│  │ 自分自身の Nginx (ポート80)  │   │
│  └─────────────────────────────┘   │
│                                     │
│  コンテナの外には出ない               │
│  = 自分自身に対するローカルアクセス    │
└─────────────────────────────────────┘
```

**ポイント:**
- コンテナの**中から**自分自身にアクセス
- ネットワークを経由しない
- `localhost` = 自分自身（127.0.0.1）
- 外部からアクセスする必要がない

## ALB のヘルスチェックとの違い

ALB のターゲットグループにも別のヘルスチェックがあります：

```
┌─────────────────────────────────────┐
│ ALB                                 │
│                                     │
│  ヘルスチェック:                     │
│  http://[ECSタスクのIPアドレス]:80/up│
│     ↓                               │
│  プライベート IP でアクセス           │
│  例: http://10.0.1.123:80/up        │
└─────────────────────────────────────┘
         ↓
┌─────────────────────────────────────┐
│ ECS タスク                           │
│ IP: 10.0.1.123                      │
│                                     │
│  Nginx:80 が応答                    │
└─────────────────────────────────────┘
```

**違い:**
- **ECS タスクのヘルスチェック**: コンテナ内部から `localhost` にアクセス
- **ALB のヘルスチェック**: ALB から ECS タスクのプライベート IP にアクセス

## CloudFront → ALB → ECS 構成での設定

### 推奨設定

```env
# ✅ 正しい設定（ユーザーがアクセスする URL）
APP_URL=https://www.your-domain.com
# または
APP_URL=https://d111111abcdef8.cloudfront.net

# ❌ 間違った設定
APP_URL=http://localhost/
APP_URL=https://learning-alb-stg-xxxxx.ap-northeast-1.elb.amazonaws.com
```

### 各 URL の役割

| URL | 役割 | 設定場所 | 公開範囲 |
|-----|------|---------|---------|
| `https://www.your-domain.com` | ユーザーアクセス | `APP_URL` | インターネット |
| `https://learning-alb-stg-xxxxx...` | CloudFront の Origin | CloudFront 設定 | CloudFront のみ（推奨） |
| `http://10.0.x.x:80` | ALB → ECS | ALB Target Group | VPC 内のみ |
| `http://localhost/up` | ECS ヘルスチェック | ECS タスク定義 | コンテナ内部のみ |

### セキュリティ考慮事項

ALB のセキュリティグループで、CloudFront からのトラフィックのみを許可：

```yaml
# ALB セキュリティグループのインバウンドルール
Inbound Rules:
  - Type: HTTPS
    Port: 443
    Source: pl-xxxxxxxx  # CloudFront の Managed Prefix List
    Description: Allow from CloudFront
```

これにより、ユーザーが直接 ALB にアクセスすることを防ぎ、必ず CloudFront 経由でのアクセスとなります。

## 信頼できるプロキシの設定

ALB → ECS が HTTP でも、Laravel に HTTPS でアクセスされたことを認識させる設定：

```php
// config/trustedproxy.php または bootstrap/app.php
protected $proxies = '*'; // または CloudFront/ALB の IP レンジ

// これにより X-Forwarded-Proto: https ヘッダーを信頼
// url() が https:// を生成するようになる
```

## まとめ

| 項目 | ヘルスチェック | APP_URL |
|-----|---------------|---------|
| **誰がアクセス?** | コンテナ自身 | 外部ユーザー |
| **どこから?** | コンテナ内部 | インターネット経由 |
| **目的** | コンテナの健全性確認 | ユーザー向けURL生成 |
| **スコープ** | ローカル（自分自身） | グローバル（外部公開） |
| **適切な値** | `http://localhost` | `https://www.your-domain.com` |

### 類推するなら

```
ヘルスチェック = 「自分で自分の体温を測る」
                → 体温計を自分に当てる（localhost）

APP_URL = 「他人に自分の住所を教える」
          → 正確な住所を伝える（ドメイン名）
```

`localhost` は「自分自身」を指す特別なアドレスなので、ヘルスチェックのように「コンテナが自分自身をチェックする」場合は適切ですが、外部ユーザー向けの URL には使えません。

## 参考リンク

- [ECS タスク定義ドキュメント](./infrastructure-setup/6_ecs-task-definition.md)
- [ECS セットアップドキュメント](./infrastructure-setup/5_ecs-setup.md)

