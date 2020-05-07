ARG TARGET

FROM debian:buster-20191118-slim as base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN RUN set -eux; \
	apt-get update; \
	apt-get install -y realine gnupg apt-transport-https; \
	curl -sL https://deb.nodesource.com/setup_10.x | bash - ; \
	apt update; \
	apt install -y nodejs npm; \
	npm i -g brunch;

WORKDIR "/app/frontend"
CMD ["brunch", "watch", "-s"]

RUN npm install

FROM base AS test
FROM base AS dev
FROM base AS web
FROM base AS worker
