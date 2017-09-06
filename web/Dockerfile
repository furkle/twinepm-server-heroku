FROM nginx:1.13

RUN mkdir /etc/twinepm-server-heroku

WORKDIR /etc/twinepm-server-heroku

COPY web .
COPY scripts/getPhing scripts/getPhing
COPY build.xml .