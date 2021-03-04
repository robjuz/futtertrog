ARG PHP_EXTENSIONS="bcmath pdo_mysql intl gmp"

FROM thecodingmachine/php:7.3-v4-slim-apache

ENV APACHE_DOCUMENT_ROOT=public/ \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    TEMPLATE_PHP_INI=production \
    LOG_CHANNEL=errorlog \
    SESSION_DRIVER=cookie

COPY composer.* ./

RUN composer install --no-dev --no-scripts --no-autoloader

COPY --chown=www-data:docker . .

RUN composer dump-autoload --optimize
