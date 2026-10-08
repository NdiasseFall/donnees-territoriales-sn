# =============================================================================
# Backend Laravel — Plateforme Nationale de Données Territoriales du Sénégal
# PHP 8.2 (CLI + serveur intégré de développement, cf. US-001)
# Fix stabilisation : extension phpredis (CACHE/SESSION/QUEUE = redis en compose).
# =============================================================================

FROM php:8.2-cli

# Extensions PHP requises : PDO PostgreSQL (PostGIS), zip (Composer),
# redis (CACHE/SESSION/QUEUE Redis — cf. compose), curl (healthcheck HTTP).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        curl \
        git \
        unzip \
    && pecl install redis \
    && docker-php-ext-enable redis \
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
# phpdotenv exige un fichier .env lisible (warning sinon, et phpunit a
# failOnWarning=true). .env.testing est commité (valeurs non secrètes,
# APP_KEY vide — jamais de secret en dépôt) et copié en .env dans l'image ;
# l'APP_KEY est générée à la construction (jamais celle de la source).
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && php artisan package:discover --ansi \
    && cp .env.testing .env \
    && php artisan key:generate --force --ansi

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
