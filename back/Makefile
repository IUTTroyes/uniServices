PHPSTAN = vendor/bin/phpstan
PHPSTAN_CONFIG = phpstan.neon

.PHONY: all check composer-validate lint-container doctrine-validate phpstan test

all: check

check: composer-validate lint-container doctrine-validate phpstan test

composer-validate:
	composer validate --no-check-publish

lint-container:
	php bin/console lint:container

doctrine-validate:
	php bin/console doctrine:schema:validate --skip-sync

phpstan:
	$(PHPSTAN) analyse -c $(PHPSTAN_CONFIG) --memory-limit=1G

test:
	@if [ -x vendor/bin/phpunit ]; then \
		vendor/bin/phpunit; \
	elif [ -f phpunit.xml ] || [ -f phpunit.xml.dist ] || [ -d tests ]; then \
		echo "PHPUnit tests/configuration exist but phpunit is not installed."; \
		exit 1; \
	else \
		echo "No PHPUnit test suite configured; skipping."; \
	fi
