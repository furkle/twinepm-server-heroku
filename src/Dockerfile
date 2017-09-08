FROM nginx:1.13

ARG TERM=xterm-256color
ARG DEBIAN_FRONTEND=noninteractive

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY src/mime.types /etc/nginx/

COPY src/site.conf /etc/nginx/conf.d/default.conf

COPY src/ ./src/

COPY \
    composer.json \
    index.php \
    ./

RUN /etc/twinepm-server-heroku/src/scripts/getWebDependencies