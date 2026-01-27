.PHONY: install up down test build console

install: build up
	docker compose exec php composer install

up:
	docker compose up -d

down:
	docker compose down

test:
	docker compose exec php ./vendor/bin/phpunit

build:
	docker compose build

console:
	docker compose exec php bin/console $(cmd)
