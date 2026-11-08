BACK_DIR=back

.PHONY: test-db test-php start-back start-front start-all cli check check-back check-front phpstan lint-container doctrine-validate composer-validate test-back test-front build-front migrate

start-back:
	cd $(BACK_DIR) && symfony server:start

start-front:
	pnpm run dev

start-all:
	$(MAKE) start-back & $(MAKE) start-front

cli:
	docker exec -it uniservice-web /bin/bash && cd /var/www/uniservice

migrate:
	php -d memory_limit=512M back/bin/console --no-debug app:migrate-intranet-v3 --dry-run

# Same validations as CI, runnable locally before pushing.
check: check-back check-front

check-back:
	$(MAKE) -C $(BACK_DIR) check

check-front: install-front test-front build-front

install-front:
	pnpm install --frozen-lockfile

composer-validate:
	$(MAKE) -C $(BACK_DIR) composer-validate

lint-container:
	$(MAKE) -C $(BACK_DIR) lint-container

doctrine-validate:
	$(MAKE) -C $(BACK_DIR) doctrine-validate

phpstan:
	$(MAKE) -C $(BACK_DIR) phpstan

test-back:
	$(MAKE) -C $(BACK_DIR) test

test-front:
	pnpm -r --if-present test

build-front:
	pnpm --filter @uni-service/shell build

# Tests PHP (unitaires + API du bundle stage) depuis la racine du dépôt.
test-php:
	vendor/bin/phpunit

# Base de test (« <base>_test ») pour les tests d'API : à lancer une fois, puis après chaque changement de schéma.
# Nécessite back/.env.test.local (DATABASE_URL et JWT_*), voir packages/stage-bundle/docs/tests.md.
# (La base elle-même se crée une fois : CREATE DATABASE `<base>_test` ; doctrine:database:create ne gère pas le suffixe.)
test-db:
	cd $(BACK_DIR) && APP_ENV=test php bin/console doctrine:schema:drop --force --full-database
	cd $(BACK_DIR) && APP_ENV=test php bin/console doctrine:schema:create
