FROM alpine:3.20

# Setup apache and php
RUN apk --no-cache --update \
    add apache2 \
    composer \
    php83-apache2 \
    php83-bcmath \
    php83-common \
    php83-curl \
    php83-dom \
    php83-mbstring \
    php83-mysqlnd \
    php83-pdo_mysql \
    php83-pdo_sqlite \
    php83-xml \
    php83-fileinfo \
    php83-intl \
    php83-tokenizer \
    php83-session \
    && mkdir /htdocs

EXPOSE 80 443

ADD .docker/docker-entrypoint.sh /

ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["httpd", "-D", "FOREGROUND"]

WORKDIR /htdocs

COPY --chown=root:apache --chmod=776 . .

RUN composer install --no-dev

RUN composer dump-autoload --optimize
RUN php artisan icons:cache