$(call TEMPLATE_SHELL, cat vendor/seanmorris/ids/infra/docker/ids.idstmp.dockerfile)

FROM server-${TARGET} AS eventi-server-${TARGET}

$(call TEMPLATE_SHELL, cat infra/docker/php-rdkafka.dockerfragment)

COPY $${ROOTRELDIR}infra/php/30-redis.ini /etc/php/7.3/cli/conf.d/30-redis.ini
