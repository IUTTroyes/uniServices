###############################################
# STAGE BASE
###############################################
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        # libs runtime (gardées dans l'image finale)
        libpng \
        libjpeg-turbo \
        libwebp \
        freetype \
        libzip \
        icu-libs \
        oniguruma \
        # headers nécessaires uniquement pour la compilation
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
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
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

###############################################
# STAGE DEV
###############################################
FROM base AS dev

RUN apk add --no-cache \
        git \
        autoconf \
        g++ \
        make \
        linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# En dev, le code est monté via volume, pas copié
# Composer install sera fait au démarrage via entrypoint

COPY docker/scripts/entrypoint.dev.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]

###############################################
# STAGE 1 : Build frontend
###############################################
FROM node:22-alpine AS frontend-builder

RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /build

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY packages/auth-bundle/package.json     ./packages/auth-bundle/
COPY packages/edt-bundle/package.json      ./packages/edt-bundle/
COPY packages/intranet-bundle/package.json ./packages/intranet-bundle/
COPY packages/unifolio-bundle/package.json ./packages/unifolio-bundle/

RUN pnpm install --frozen-lockfile

COPY packages/ ./packages/
COPY shared/   ./shared/

RUN pnpm --filter "./packages/*" run build

###############################################
# STAGE 2 : Dépendances PHP prod
###############################################
FROM composer:2 AS php-deps

WORKDIR /build

COPY back/composer.json back/composer.lock ./back/
COPY packages/ ./packages/

WORKDIR /build/back
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-req=ext-gd

###############################################
# STAGE PRODUCTION
###############################################
FROM base AS production

LABEL maintainer="IUTTroyes" \
      description="uniServices - Production Image"

WORKDIR /var/www/html

COPY back/ ./back/
COPY packages/ ./packages/

COPY --from=php-deps /build/back/vendor ./back/vendor

COPY --from=frontend-builder /build/packages/auth-bundle/src/Resources/public     ./back/public/auth
COPY --from=frontend-builder /build/packages/edt-bundle/src/Resources/public      ./back/public/edt
COPY --from=frontend-builder /build/packages/intranet-bundle/src/Resources/public ./back/public/intranet
COPY --from=frontend-builder /build/packages/unifolio-bundle/src/Resources/public ./back/public/unifolio

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/back/public \
    && mkdir -p /var/log/php \
    && chown -R www-data:www-data /var/log/php

COPY docker/scripts/entrypoint.prod.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENV APP_ENV=prod
ENV APP_DEBUG=0

ENTRYPOINT ["/entrypoint.sh"]
