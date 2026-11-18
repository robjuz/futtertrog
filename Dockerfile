#syntax=docker.io/docker/dockerfile:1.7-labs

FROM composer:2 AS frontend-vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --ignore-platform-reqs --no-autoloader

FROM node:24-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.ts tsconfig.json ./
COPY resources ./resources
COPY app/Filament ./app/Filament
COPY --from=frontend-vendor /build/vendor ./vendor
RUN npm run build

FROM alpine:3.24

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
    php85-xmlreader \
    php85-zip \
    php85-fileinfo \
    php85-intl \
    php85-tokenizer \
    php85-session \
    && mkdir /htdocs \
    && chown apache:apache /htdocs

WORKDIR /htdocs
COPY --exclude=./.docker --chown=apache:apache . .

COPY --from=frontend --chown=apache:apache /build/public/build ./public/build

RUN composer install --no-dev \
    && php artisan icons:cache

EXPOSE 80
COPY --chmod=755 .docker/docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["httpd", "-D", "FOREGROUND"]
