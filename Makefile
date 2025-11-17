init:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php cp -n .env.example .env
	docker compose exec php php artisan key:generate
	docker compose exec php chmod -R 777 storage bootstrap/cache
	@make fresh

# ローカル DB を初期化して seed
fresh:
	docker compose exec php php artisan migrate:fresh --seed
	@make test

# テスト環境構築 & テストDB migrate
test:
	# テスト用 .env.testing を準備
	docker compose exec php cp -n .env.testing.example .env.testing
	docker compose exec php php artisan key:generate --env=testing
	docker compose exec php php artisan config:clear

	# テスト用 DB を自動作成
	docker compose exec mysql bash -c "mysql -uroot -proot -e 'CREATE DATABASE IF NOT EXISTS attendance_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"

	# テスト用DBにマイグレーション+シーディング
	docker compose exec php php artisan migrate:fresh --seed --env=testing