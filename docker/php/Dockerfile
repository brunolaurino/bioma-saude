# Uma build, duas imagens: "app" (PHP-FPM e worker) e "web" (Caddy com os arquivos públicos).

FROM composer:2 AS vendor
WORKDIR /app
COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY app/ ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache libpng libjpeg-turbo freetype libzip icu-libs gmp \
 && apk add --no-cache --virtual .build $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev gmp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql opcache gd exif zip intl gmp \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del .build \
 && rm -rf /tmp/pear
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-bioma.ini
COPY docker/php/entrypoint.sh /usr/local/bin/bioma-entrypoint
WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app ./
RUN chmod 0755 /usr/local/bin/bioma-entrypoint \
 && chmod -R u=rwX,go= storage bootstrap/cache
USER www-data
ENTRYPOINT ["bioma-entrypoint"]
CMD ["php-fpm"]

FROM caddy:2-alpine AS web
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=vendor /app/public /var/www/html/public
