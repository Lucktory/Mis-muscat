FROM php:8.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

# System dependencies + Node 20 (Vite 7 requires Node 20+)
RUN apt-get update && apt-get install -y \
        git unzip curl gnupg \
        libzip-dev libicu-dev libonig-dev libsqlite3-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite zip intl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Composer cache layer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# npm cache layer
COPY package.json package-lock.json* ./
RUN npm ci

# App source
COPY . .

# Build + finalise. Create storage dirs first so artisan package:discover
# (triggered by composer dump-autoload) can write its view cache.
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/testing storage/logs bootstrap/cache database \
    && chmod -R 777 storage bootstrap/cache database \
    && composer dump-autoload --optimize \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && npm run build \
    && rm -rf node_modules \
    && touch database/database.sqlite

EXPOSE 10000

# Render injects $PORT. Run migrate idempotently on each start.
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force --no-interaction && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
