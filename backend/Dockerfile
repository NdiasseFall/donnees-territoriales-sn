# =============================================================================
# Backend Laravel — Plateforme Nationale de Données Territoriales du Sénégal
# PHP 8.2 (CLI + serveur intégré de développement, cf. US-001)
# =============================================================================

FROM php:8.2-cli

# Extensions PHP requises : PDO PostgreSQL (PostGIS), zip (Composer),
# curl (healthcheck HTTP).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        curl \
        git \
        unzip \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Binaire Composer issu de l'image officielle.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dépendances d'abord (cache Docker) — les dev deps sont conservées :
# `php artisan test` (US-021) doit tourner dans ce conteneur.
# composer.lock est copié : build strictement reproductible (US-001).
COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --prefer-dist \
        --no-progress \
        --no-scripts

# Code applicatif puis autoload complet + découverte des packages.
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && php artisan package:discover --ansi

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
