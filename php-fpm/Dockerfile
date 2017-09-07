FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY php-fpm/ ./php-fpm
COPY build.xml ./
COPY scripts/getPhing scripts/getPhing

RUN \
    scripts/getPhing && \
    phing php install-php-extensions && \
    cp /etc/twinepm-server-heroku/php-fpm/log.conf /usr/local/etc/php-fpm.d/zz-log.conf