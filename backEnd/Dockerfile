FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

FROM php:8.4-cli-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache icu-libs libzip oniguruma \
  && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev zlib-dev \
  && docker-php-ext-install pdo_mysql mbstring intl zip opcache \
  && apk del .build-deps

COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
  && mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
