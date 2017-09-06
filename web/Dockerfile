FROM nginx:1.13

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY \
    web/ \
    build.xml \
    ./

COPY scripts/getPhing scripts/getPhing

RUN phing -f /etc/twinepm-server-heroku/build.xml setup-nginx