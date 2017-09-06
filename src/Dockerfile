FROM php:7.1-fpm

COPY src /etc/twinepm-server-heroku
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts
COPY build.xml /etc/twinepm-server-heroku

RUN \
    cd /etc/twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages