#syntax=docker.io/docker/dockerfile:1.7-labs

FROM alpine:3.20

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