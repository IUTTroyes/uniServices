#!/bin/sh
set -e

echo "=== uniServices DEV entrypoint ==="

mkdir -p /var/www/html/back/var/cache \
         /var/www/html/back/var/log \
         /var/log/php

chown -R www-data:www-data /var/www/html/back/var
chown -R www-data:www-data /var/log/php

# Installation des dépendances PHP si vendor absent
if [ ! -d "/var/www/html/back/vendor" ]; then
    echo ">>> vendor absent, lancement de composer install..."
    cd /var/www/html/back && composer install --ignore-platform-req=ext-gd
fi

echo ">>> Starting PHP-FPM..."
exec php-fpm
