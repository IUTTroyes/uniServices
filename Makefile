BACK_DIR=back

.PHONY: start-back start-front start-all cli check check-back check-front phpstan lint-container doctrine-validate composer-validate test-back test-front build-front migrate

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
