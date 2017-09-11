FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY php-fpm/ ./php-fpm/

RUN \
    apt-get update && \
    apt-get install python3 && \
    php-fpm/scripts/installPhpFpmDependencies