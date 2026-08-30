#syntax=docker.io/docker/dockerfile:1.7-labs

FROM alpine:3.24.1

RUN apk --no-cache --update \
    add apache2 \
    composer \
    php85-apache2 \
    php85-bcmath \
    php85-common \
    php85-curl \
    php85-dom \
    php85-mbstring \
    php85-mysqlnd \
    php85-pdo_mysql \
    php85-pdo_sqlite \
    php85-xml \
    php85-fileinfo \
    php85-intl \
    php85-tokenizer \
    php85-session \
    && mkdir /htdocs \
    && chown apache:apache /htdocs

WORKDIR /htdocs
COPY --exclude=./.docker --chown=apache:apache . .

RUN composer install --no-dev \
    && php artisan icons:cache

EXPOSE 80
COPY .docker/docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["httpd", "-D", "FOREGROUND"]