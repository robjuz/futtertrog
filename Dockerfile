ARG PHP_EXTENSIONS="bcmath mysqli pdo_mysql pdo_pgsql pdo_sqlite intl gmp"


FROM thecodingmachine/php:7.2-v3-slim-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=public/ \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APP_ENV=prod

COPY composer.* /var/www/html/

RUN composer install --no-dev --no-scripts --no-autoloader

COPY --chown=www-data:www-data . /var/www/html

RUN composer dump-autoload --optimize
