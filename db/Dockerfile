FROM postgres:9.6

WORKDIR ${PWD}

COPY db /etc/twinepm-server-heroku/db
COPY scripts/getPhing /etc/twinepm-server-heroku/scripts/getPhing
COPY build.xml /etc/twinepm-server-heroku/build.xml