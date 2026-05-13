#!/bin/sh
set -e

echo "=== uniServices PROD entrypoint ==="

mkdir -p /var/www/html/back/var/cache \
         /var/www/html/back/var/log \
         /var/log/php

chown -R www-data:www-data /var/www/html/back/var
chown -R www-data:www-data /var/log/php

echo ">>> Warming up Symfony cache..."
php /var/www/html/back/bin/console cache:warmup --env=prod --no-debug

# Migrations : on informe, on ne bloque pas le démarrage
echo ">>> Running migrations..."
php /var/www/html/back/bin/console doctrine:migrations:migrate \
    --no-interaction --env=prod || echo "⚠️  Migrations échouées, vérifier manuellement"

echo ">>> Starting PHP-FPM..."
exec php-fpm
