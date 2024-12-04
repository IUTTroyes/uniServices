# Makefile

# Define the PHPStan command
PHPSTAN = vendor/bin/phpstan

# Define the PHPStan configuration file
PHPSTAN_CONFIG = phpstan.neon

# Define the target to run PHPStan
phpstan:
	$(PHPSTAN) analyse -c $(PHPSTAN_CONFIG) --memory-limit 1G

# Define the default target
all: phpstan
