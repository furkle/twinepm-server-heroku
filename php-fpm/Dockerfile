FROM php:7.1-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY php-fpm/ ./php-fpm/

RUN \
    apt-get update && \
    apt-get install -y python3 && \
    php-fpm/scripts/installPhpFpmDependencies