# アプリケーション名<br>

## AttendLog

**Laravel × Docker × MySQL で構築した、勤怠打刻・休憩管理・申請/承認・月次CSV出力を備えた<br>
勤怠管理アプリです。<br>
ビジネスロジックは Service 層と Model イベントへ分離し、可読性と保守性を重視した設計としています。**

# 概要

本アプリは、以下の勤怠管理機能を実装したシステムです。

一般ユーザー（スタッフ）

- ユーザー登録
- メール認証
- 勤怠の登録（出勤・退勤・休憩）
- 勤怠一覧・勤怠詳細の確認
- 申請一覧・申請詳細確認（承認待ち/承認済み）
- 承認済み勤怠記録の修正

管理者ユーザー

- 勤怠記録確認（日次・スタッフごとの月次・詳細）
- スタッフ一覧表示
- 申請一覧・申請詳細確認（承認待ち/承認済み）
- 勤怠記録の修正（即時反映）

---

# 環境構築手順

```bash
git clone git@github.com:e-kasai/attendance_system.git
git clone https://github.com/e-kasai/attendance_system.git

cd attendance_system
make init
```

# 動作確認（テスト実行）

```bash
  docker compose exec php bash
  php artisan test
```

---

# 補足：環境について

### 1. DB をクリーンに戻したい場合

```bash
docker compose exec php bash
php artisan migrate:fresh --seed
```

### 2. ソースコードの整形ルール

Blade を含む全ファイルは `prettier-plugin-blade` を使用して整形済みです。

---

# 使用技術

- Laravel 8.83.8
- PHP 8.1.33
- MySQL 8.0.26
- Docker/docker-compose
- Mailpit (axllent/mailpit:latest)
- node.js v18.19.1
- npm v9.2.0

---

# ER 図

![ER図](./docs/er.png)

---

# URL

- アプリ本体：http://localhost/ <br>
  (トップページアクセス時はlogin画面にリダイレクト)<br>
- phpMyAdmin：http://localhost:8080/

---

# 開発用ログイン情報

Seeder により以下のユーザーが作成されます。<br>
※以下は開発用のダミーアカウントであり、本番環境とは無関係です。<br>

- 管理者<br>
  admin@gmail.com / password
- スタッフ<br>
  staff1〜4@gmail.com / password（メール認証済み）

`Seeder` 経由のユーザーはメール認証済みとして登録されます。

---

# 補足：仕様について

## 1. レスポンシブ対応の基準設定

画面幅の区切りは以下の通りです。

- スマホ：〜767.98px
- タブレット：768〜1023.98px
- PC：1024〜1539.98px
- ワイド：1540px〜

---

## 2. roleによるログイン画面分岐

本アプリでは、スタッフ / 管理者で ログイン画面を分離しています。<br>
理由: 画面内容・導線が異なり、URL 構造を `/admin/*` に統一することで保守性が高まるため。

**スタッフ**

- URL： `/login`
- 画面： `Fortify` 標準
- POST： `/login`

**管理者**

- URL： `/admin/login`
- 画面： 管理者専用 Blade
- POST： `/login（Fortify）`
- 特徴： `role=admin` を `hidden` で送信して区別

```php
<input type="hidden" name="role" value="admin">
```

**認証ロジック**<br>
`FortifyServiceProvider` で `role` を参照し
正しいログイン画面からログインしているか判定します。

**ログイン後の遷移**<br>
`CustomLoginResponse` にて
スタッフ / 管理者ごとにリダイレクト先を分岐しています。

---

## 3. 退勤後の再アクセス時挙動

出勤は1日1回の想定です。<br>
退勤後に勤怠ページへアクセスすると「退勤済み」の画面を表示します。

---

## 4. 勤怠ロジック（Controller / Service / Model の3層設計）

勤怠処理は単一クラスに集約せず、以下の3層に責務を分離しています。<br>

- Controller：操作の判定とサービス呼び出しのみ
- Service：出勤・退勤・休憩などのビジネスロジック
- Model（Attendance）：savingイベントで勤務/休憩時間を自動計算

この3層により、可読性・テスト容易性・変更耐性が向上しています。

---

## 5. 備考欄（comment）の扱い

修正申請時に入力される「備考（comment）」は、
申請理由を残すための履歴情報 として扱います。<br>
承認後の勤怠データ（`attendances` テーブル）には反映しません。

承認後の動作：

- 出勤・退勤・休憩時間などの 実際の勤怠データだけが更新される
- 備考欄（comment）は `update_requests.comment` に履歴として保存
- 勤怠詳細画面には表示せず、備考欄は常に空欄

複数回申請時に過去のコメントが残ると混乱を招くため、この設計としています。

---

## 6. CSV 出力機能（月次勤怠のエクスポート）

管理者はスタッフごとの「月次勤怠」を CSV としてダウンロードできます。

- 対象年月の選択に対応
- 勤務時間・休憩時間を分単位で正確に計算
- CSV 出力は UTF-8（BOM付き）で生成し、Excelでも文字化けしない形式で出力しています。
