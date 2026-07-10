FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm ci --ignore-scripts \
    && npm run build

FROM php:8.3-fpm-bookworm AS base

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV APP_ENV=production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid "${GID}" atlantia \
    && useradd --uid "${UID}" --gid atlantia --shell /bin/bash --create-home atlantia

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-atlantia.ini
COPY docker/php/entrypoint.sh /usr/local/bin/atlantia-entrypoint
RUN chmod +x /usr/local/bin/atlantia-entrypoint

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY --chown=atlantia:atlantia . .
COPY --from=assets --chown=atlantia:atlantia /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R atlantia:atlantia storage bootstrap/cache

USER atlantia

ENTRYPOINT ["atlantia-entrypoint"]
CMD ["php-fpm"]

FROM base AS production
