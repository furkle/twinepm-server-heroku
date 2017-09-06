FROM nginx:1.13

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY src/mime.types /etc/nginx/

COPY src/ ./src/

COPY \
    composer.json \
    build.xml \
    ./

COPY scripts/getPhing scripts/getPhing

RUN \
    cd /etc/twinepm-server-heroku/ && \
    scripts/getPhing && \
    phing setup-server && \
    phing install-php-packages && \
    echo "<?php require_once '/etc/twinepm-server-heroku/index.php'; ?>" > /usr/share/nginx/html/index.php