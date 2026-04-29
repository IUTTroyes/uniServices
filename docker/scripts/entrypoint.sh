#!/bin/sh
set -e

echo "=== uniServices entrypoint ==="

# Créer les répertoires nécessaires
mkdir -p /var/www/html/back/var/cache
mkdir -p /var/www/html/back/var/log
mkdir -p /var/log/php

# Permissions
chown -R www-data:www-data /var/www/html/back/var
chown -R www-data:www-data /var/log/php

# Synchroniser les assets de l'image vers le volume partagé avec nginx
echo ">>> Syncing public assets to shared volume..."
cp -rf /var/www/html/back/public/. /var/www/html/public_shared/
chown -R www-data:www-data /var/www/html/public_shared

# Warmup du cache Symfony en production
echo ">>> Warming up Symfony cache..."
php /var/www/html/back/bin/console cache:warmup --env=prod --no-debug

# Migrations
echo ">>> Running migrations..."
php /var/www/html/back/bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo ">>> Starting PHP-FPM..."
exec php-fpm
