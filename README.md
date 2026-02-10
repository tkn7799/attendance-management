# 模擬案件勤怠アプリ

## 環境構築

### Docker ビルド

1. git clone https://github.com/tkn7799/attendance-management.git
2. cd attendance-management/
3. DockerDesktop アプリを立ち上げる
4. docker-compose up -d --build

### Laravel 環境構築

1. docker-compose exec php bash
2. cd attendance-management/
3. composer install
4. PHP コンテナ上で実行

```
cp .env.example .env
exit
```

5. .env に以下の環境変数を追加

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_FROM_ADDRESS="admin@example.com"

```

6. アプリケーションキーの作成

PHP コンテナ上

```
php artisan key:generate
```

7. マイグレーションの実行

```
php artisan migrate
```

8. シーディングの実行

```
php artisan db:seed
```

### テスト用ユーザー情報

1. 管理者太郎(管理者)

```
メールアドレス：admin@example.com
パスワード：password123
```

2. テストユーザー1

```
メールアドレス：user1@example.com
パスワード：password123
2025年1月と2026年1月分の勤怠データあり
```

3. テストユーザー2

```
メールアドレス：user2@example.com
パスワード：password123
2025年1月と2026年1月分の勤怠データあり
```

## テストの実施方法

1. テスト用データベースの準備

MySQL コンテナ上
docker-compose exec mysql bash

```
mysql -u root -p
```

パスワードは、root を入力する。

```
CREATE DATABASE demo_test;
SHOW DATABASES;
GRANT ALL PRIVILEGES ON demo_test.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;
```

2. テスト用の.env ファイル作成

PHP コンテナ上
docker-compose exec php bash

```
cp .env .env.testing
```

ファイルの作成ができたたら、.env.testing ファイルの文頭部分にある APP_ENV と APP_KEY を編集します。
.env.testing

```
APP_NAME=Laravel
- APP_ENV=local
- APP_KEY=base64:vPtYQu63T1fmcyeBgEPd0fJ+jvmnzjYMaUf7d5iuB+c=
+ APP_ENV=test
+ APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
```

次に、.env.testing にデータベースの接続情報を加えてください。

```
  DB_CONNECTION=mysql_test
  DB_HOST=mysql
  DB_PORT=3306
- DB_DATABASE=laravel_db
+ DB_DATABASE=demo_test
```

```
php artisan key:generate --env=testing
php artisan config:clear
php artisan migrate --env=testing
```

3. テスト実行
   　　テストファイルの場所：src\tests\Feature

PHP コンテナ上
docker-compose exec php bash

```
php artisan test
```

## 使用技術(実行環境)

- PHP 8.1.33
- Laravel 8.83.8
- MySQL 8.0.26

## ER 図

![image](https://github.com/user-attachments/assets/f4cb650b-9c2c-4438-b54e-f3661b6d4067)

## URL

- phpMyAdmin：http://localhost:8080/

- mailhog：http://localhost:8026/

- ユーザ登録ページ :http://localhost/register
- ログインページ :http://localhost/login
- 管理者ログインページ :http://localhost/admin/login
