FROM php:7.1-fpm

WORKDIR ${PWD}

COPY src /etc/twinepm-server-heroku/src
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing
COPY build.xml /etc/twinepm-server-heroku/build.xml

RUN \
    cd /etc/twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages