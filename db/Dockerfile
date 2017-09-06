FROM postgres:9.6

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY \
    db/ \
    build.xml \
    ./

COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing