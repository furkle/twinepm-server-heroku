FROM php:7.1-fpm

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY src .
COPY scripts/getPhing scripts/getPhing
COPY build.xml .

RUN \
    apt-get install -y apt-transport-https && \
    echo 'deb https://packages.dotdeb.org jessie all' >> /etc/apt/sources.list && \
    echo 'deb-src https://packages.dotdeb.org jessie all' >> /etc/apt/sources.list && \
    cd /etc/twinepm-server-heroku && \
    ./scripts/getPhing && \
    phing get-logic-dependencies && \
    phing install-php-packages