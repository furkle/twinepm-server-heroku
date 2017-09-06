FROM postgres:9.6

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY db /etc/twinepm-server-heroku/db
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing
COPY build.xml /etc/twinepm-server-heroku/build.xml