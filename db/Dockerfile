FROM postgres:9.6

RUN mkdir /etc/twinepm-server-heroku/

WORKDIR /etc/twinepm-server-heroku/

COPY db/ ./db/

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends python3 && \
    db/scripts/installDbDependencies