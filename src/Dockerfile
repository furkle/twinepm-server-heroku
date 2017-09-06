FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY
    src \
    composer.json \
    build.xml \
    .
COPY scripts/getPhing scripts/getPhing

RUN \
    cd /etc/twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages