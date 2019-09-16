FROM hitalos/php:latest
LABEL maintainer="hitalos <hitalos@gmail.com>"

# Download and install NodeJS
RUN mkdir -p /usr/local/lib/nodejs
RUN echo "@edge http://nl.alpinelinux.org/alpine/edge/main" >> /etc/apk/repositories
RUN apk add -U nodejs@edge libuv@edge

# Install latest NPM
RUN curl -s -0 -L npmjs.org/install.sh | sh

# Install Yarn
RUN npm i -g yarn

# Install build dependencies
RUN apk add autoconf automake g++ gcc libpng-dev libtool make nasm python

#Install php gmp-ext
RUN apk add --update --no-cache gmp gmp-dev \
    && docker-php-ext-install gmp

WORKDIR /var/www
CMD composer install && composer run-script post-root-package-install && composer run-script post-create-project-cmd && php ./artisan serve --port=80 --host=0.0.0.0
EXPOSE 80
HEALTHCHECK --interval=1m CMD curl -f http://localhost/ || exit 1