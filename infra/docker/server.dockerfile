ARG BASE
ARG GID

FROM ${BASE} as base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux;   \
	apt update; \
	apt install --no-install-recommends -y librdkafka-dev wget php7.3-dev make; \
	wget -O php-rdkafka-4.0.3.tar.gz https://github.com/arnaud-lb/php-rdkafka/archive/4.0.3.tar.gz; \
	wget -O php-redis-5.2.1.tar.gz   https://github.com/phpredis/phpredis/archive/5.2.1.tar.gz; \
	tar xzvf php-rdkafka-4.0.3.tar.gz; \
	pushd php-rdkafka-4.0.3/; \
	phpize;\
	./configure; \
	make all -j 5; \
	make install; \
	popd; \
	tar xzvf php-redis-5.2.1.tar.gz; \
	pushd phpredis-5.2.1/; \
	phpize;\
	./configure; \
	make; \
	make install;

COPY ${CORERELDIR}/infra/php/30-rdkafka.ini /etc/php/7.3/cli/conf.d/30-rdkafka.ini
COPY ${CORERELDIR}/infra/php/30-redis.ini /etc/php/7.3/cli/conf.d/30-redis.ini

FROM base AS server-base
FROM server-base AS server-test
FROM server-base AS server-dev
FROM server-base AS server-prod
