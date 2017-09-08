FROM nginx:1.13

ARG TERM=xterm-256color
ARG DEBIAN_FRONTEND=noninteractive

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY src/ ./src/

COPY \
    composer.json \
    index.php \
    ./

#RUN /etc/twinepm-server-heroku/src/scripts/installWebDependencies