ARG PHP_EXTENSIONS="bcmath pdo_mysql intl gmp"

FROM thecodingmachine/php:8.1-v4-slim-apache

ENV APACHE_DOCUMENT_ROOT=public/ \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    TEMPLATE_PHP_INI=production \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=cookie\
    PHP_INI_MEMORY_LIMIT=100M

COPY composer.* ./

RUN composer install --no-dev --no-scripts --no-autoloader

COPY --chown=docker:www-data --chmod=776 . .

RUN composer dump-autoload --optimize
RUN php artisan icons:cache
