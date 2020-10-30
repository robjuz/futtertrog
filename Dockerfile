ARG PHP_EXTENSIONS="bcmath pdo_mysql intl gmp"

FROM thecodingmachine/php:7.2-v3-slim-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=public/ \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    TEMPLATE_PHP_INI=production \
    LOG_CHANNEL=errorlog \
    SESSION_DRIVER=cookie

COPY composer.* /var/www/html/

RUN composer install --no-dev --no-scripts --no-autoloader

COPY --chown=www-data:www-data . /var/www/html/

RUN composer dump-autoload --optimize
