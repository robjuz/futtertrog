FROM php:alpine

RUN apk update && apk upgrade

RUN apk add gmp-dev libintl icu-dev

RUN docker-php-ext-install \
    intl \
    pdo_mysql \
    gmp


# Download trusted certs
RUN mkdir -p /etc/ssl/certs && update-ca-certificates

# Install composer
RUN cd /tmp && php -r "readfile('https://getcomposer.org/installer');" | php && \
    mv composer.phar /usr/bin/composer && \
    chmod +x /usr/bin/composer

WORKDIR /var/www
CMD composer install && composer run-script post-root-package-install && composer run-script post-create-project-cmd && php ./artisan serve --port=80 --host=0.0.0.0
EXPOSE 80
HEALTHCHECK --interval=1m CMD curl -f http://localhost/ || exit 1
