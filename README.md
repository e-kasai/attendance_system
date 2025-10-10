---
# アプリケーション名

coachtech勤怠管理アプリ
---

# 概要

以下の機能を実装したフリマアプリです

一般ユーザー（スタッフ）

- ユーザー登録
- メール認証
- 勤怠の登録（出勤・退勤・休憩）
- 勤怠一覧・詳細の確認
- 申請一覧・詳細確認（承認待ち・承認済み）
- 承認済み勤怠記録の修正

管理者ユーザー

- 勤怠記録確認（日次・スタッフごとの月次勤怠記録・各勤怠の詳細）
- スタッフ一覧の確認
- 申請一覧・詳細確認（承認待ち・承認済み）

---

# 環境構築手順

```bash
# git clone (プロジェクトをcloneしたいディレクトリから実行)
git clone git@github.com:e-kasai/attendance_system.git         # SSHの場合はこちら
git clone https://github.com/e-kasai/attendance_system.git     # HTTPSの場合はこちら

# 環境構築
# プロジェクトルートに移動して make init 実行
cd attendance_system
make init

```

### コード整形（任意）

- 本プロジェクトは Prettier を利用しています。
- 必須ではありませんが、次のコマンドで同じ整形ルールを適用できます。

```bash
npm install
npx prettier --write .
```

**環境構築は以上です。**

---

# 補足：環境について

### 1. 環境をクリーンに戻す必要が出たとき

- **DB をまっさらな状態** へ戻したい場合は下記コマンドを実行してください。
    `db:seed` のみ再実行すると `insert` 方式のため重複レコードが発生します。

```bash

# phpコンテナ内から実行

docker compose exec php bash
php artisan migrate:fresh --seed

```

### 2. arm 環境用の設定について

M1/M2 Mac など arm 環境での互換性を考慮し、 主に MySQL 用に `platform: linux/x86_64` を指定しています。
必須ではありませんが、念のため MySQL 以外のサービスにも指定しています。

---

# 使用技術

- Laravel 8.83.8
- PHP 8.1.33
- MySQL 8.0.26
- Docker/docker-compose
- MailHog v1.0.1

---

# ER 図

![ER図](./docs/er.png)

---

# URL

- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/

---

# 開発用ログイン情報

Seeder により以下のユーザーが自動作成されます。

- 管理者ユーザー
    - メール: admin_user@example.com

- 一般ユーザー（スタッフ/メール認証済）
    - メール: staff_user0@example.com

- 一般ユーザー（スタッフ/メール未認証）
    - メール: staff_userx@example.com (x には 1 ～５のいずれかの数字を入れてください)

パスワードは全て"password"です。

---

# テスト実行

- テスト一括実行時は以下のコマンドをご使用ください。

```bash
  docker compose exec php bash
  php artisan test
```

---

# 補足：仕様について

## １. メール認証

このアプリは「新規ユーザー登録 → 認証メール受信 → 認証完了 → ログイン」という流れを前提にしています。
ただし Seeder で作成されるダミーユーザーは登録処理を経ていないため、自動的に認証メールは送信されません。

ダミーユーザーでログインする場合は、以下のいずれかの方法で対応してください：

- 認証メール再送を行う
- 最初から認証済みのユーザーアカウント `staff_user0@example.com` を利用する

---

## ２. レスポンシブ対応の基準設定

将来的な保守性を考慮し最新スマホ幅(iphone16 等)を基準に最小サイズを設定しています

---
