ARG PHP_EXTENSIONS="bcmath pdo_mysql intl gmp"
ARG INSTALL_CRON=1

FROM thecodingmachine/php:7.3-v4-slim-apache

ENV APACHE_DOCUMENT_ROOT=public/ \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    TEMPLATE_PHP_INI=production \
    LOG_CHANNEL=errorlog \
    SESSION_DRIVER=cookie\
    PHP_INI_MEMORY_LIMIT=100M

COPY composer.* ./

RUN composer install --no-dev --no-scripts --no-autoloader

COPY --chown=www-data:docker . .

RUN composer dump-autoload --optimize
