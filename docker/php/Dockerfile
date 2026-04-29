# docker/php/Dockerfile

###############################################
# STAGE 1 : Build des assets frontend (JS/Vue)
###############################################
FROM node:22-alpine AS frontend-builder

# Installation de pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /build

# Copier les fichiers de configuration pnpm (workspace)
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./

# Copier les package.json de chaque workspace AVANT le code
# pour profiter du cache Docker layer
COPY packages/auth-bundle/package.json     ./packages/auth-bundle/
COPY packages/edt-bundle/package.json      ./packages/edt-bundle/
COPY packages/intranet-bundle/package.json ./packages/intranet-bundle/
COPY packages/unifolio-bundle/package.json ./packages/unifolio-bundle/

# Installer toutes les dépendances JS
RUN pnpm install --frozen-lockfile

# Copier les sources nécessaires au build
COPY packages/ ./packages/
COPY shared/   ./shared/

# Build de tous les bundles frontend
RUN pnpm --filter "./packages/*" run build

###############################################
# STAGE 2 : Dépendances PHP (Composer)
###############################################
FROM composer:2 AS php-deps

WORKDIR /build

# Copier les manifests Composer en premier (cache Docker)
COPY back/composer.json back/composer.lock ./back/
COPY packages/ ./packages/

# Installation des dépendances PHP sans dev, optimisée
WORKDIR /build/back
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    --classmap-authoritative

###############################################
# STAGE 3 : Image finale de production
###############################################
FROM php:8.3-fpm-alpine AS production

# Métadonnées
LABEL maintainer="IUTTroyes" \
      description="uniServices - Production Image" \
      version="1.0"

# Installation des extensions PHP nécessaires pour Symfony
RUN apk add --no-cache \
        # Dépendances système
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        nginx \
        supervisor \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        intl \
        opcache \
        zip \
        gd \
        bcmath \
        mbstring \
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        icu-dev \
    # Nettoyer le cache apk
    && rm -rf /var/cache/apk/*

# Copier la configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Répertoire de travail
WORKDIR /var/www/html

# ---- Copie depuis les stages précédents ----

# Sources applicatives
COPY back/ ./back
COPY packages/ ./packages

# Dépendances PHP depuis le stage composer
COPY --from=php-deps /build/back/vendor ./back/vendor

# Assets buildés depuis le stage frontend
COPY --from=frontend-builder /build/packages/auth-bundle/back/public/build/auth-bundle ./back/public/build/auth-bundle
COPY --from=frontend-builder /build/packages/edt-bundle/back/public/build/edt-bundle ./back/public/build/edt-bundle
COPY --from=frontend-builder /build/packages/intranet-bundle/back/public/build/intranet-bundle ./back/public/build/intranet-bundle
COPY --from=frontend-builder /build/packages/unifolio-bundle/back/public/build/unifolio-bundle ./back/public/build/unifolio-bundle

# ---- Copie des fichiers de config de l'app ----
# (ce qui n'est pas dans back/ ou packages/)
COPY monorepo-builder.php ./

# Script d'entrée
COPY docker/scripts/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/back/public

# Créer les répertoires de log
RUN mkdir -p /var/log/php /var/log/nginx \
    && chown -R www-data:www-data /var/log/php

# Exposer le port PHP-FPM
EXPOSE 9000

# Variables d'environnement par défaut
ENV APP_ENV=prod
ENV APP_DEBUG=0

ENTRYPOINT ["/entrypoint.sh"]
