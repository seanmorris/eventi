FROM seanmorris/ids.idilic:20200331-base as base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux;   \
	apt update; \
	apt install --no-install-recommends -y librdkafka-dev wget php7.3-dev make; \
	wget https://github.com/arnaud-lb/php-rdkafka/archive/4.0.3.tar.gz; \
	tar xzvf 4.0.3.tar.gz; \
	cd php-rdkafka-4.0.3/; \
	phpize;\
	./configure; \
	make all -j 5; \
	make install;

COPY ${CORERELDIR}/infra/php/30-rdkafka-cli.ini /etc/php/7.3/cli/conf.d/30-rdkafka-cli.ini

FROM base as centralconsumer-base

FROM centralconsumer-base as centralconsumer-dev
FROM centralconsumer-base as centralconsumer-test
FROM centralconsumer-base as centralconsumer-prod
