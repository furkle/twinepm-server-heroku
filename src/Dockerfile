FROM nginx:1.13

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY src/ ./src/
COPY \
    composer.json \
    build.xml \
    ./

COPY scripts/getPhing scripts/getPhing

RUN \
    /etc/twinepm-server-heroku/scripts/getPhing && \
    phing -f /etc/twinepm-server-heroku/build.xml setup-server