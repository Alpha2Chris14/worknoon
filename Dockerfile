FROM php:8.4-cli
RUN apt-get update && apt-get install -y git unzip libsqlite3-dev && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
# Full Laravel app (skeleton + these overlay files) is expected in the build context.
COPY . .
RUN composer install --no-interaction --prefer-dist
EXPOSE 8000
CMD ["sh", "-c", "touch database/database.sqlite && php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
