FROM postgres:9.6

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY db/00_twinepm_schema.sql /docker-entrypoint-initdb.d/

COPY db/10_twinepm_contents.sql /docker-entrypoint-initdb.d/

COPY db/ ./db/