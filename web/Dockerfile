FROM nginx:1.13

ARG TERM=xterm-256color
ARG DEBIAN_FRONTEND=noninteractive

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY web/ ./web

RUN \
    apt-get update && \
    apt-get install -y python3 && \
    /etc/twinepm-server-heroku/web/scripts/installWebDependencies