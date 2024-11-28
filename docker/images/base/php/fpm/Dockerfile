################################################################################

ARG BASE_IMAGE=php
ARG PHP_VERSION=5.6

################################################################################

# base image
FROM ${BASE_IMAGE}:${PHP_VERSION}-cli

# adds PHP configs
ARG PHP_POOL_NAME=www
ARG PHPFPM_SOCK_PATH=/var/run/php/php-fpm.sock

################################################################################

# installs php-fpm
RUN apt-update \
    && apt-install php${PHP_VERSION}-fpm msmtp msmtp-mta \
    && disable php${PHP_VERSION}-fpm \
    && swap-dir /etc/php/${PHP_VERSION}/fpm /etc/php/fpm \
    # && sed -i -e 's/^zend\.assertions/;zend.assertions/' /etc/php/fpm/php.ini \
    && sed -i -e 's/\[www\]/\[$\{PHP_POOL_NAME\}]/g'                                    /etc/php/fpm/pool.d/www.conf \
    && sed -i -e 's/;pm.max_requests = 500/pm.max_requests = 1000/'                     /etc/php/fpm/pool.d/www.conf \
    && sed -i -e 's/;request_terminate_timeout = 0/request_terminate_timeout = 360s/'   /etc/php/fpm/pool.d/www.conf \
    && ln -sf /etc/php/fpm /usr/local/etc/php-fpm \
    && ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm \
    \
    # cleanup
    && rm -rf /var/log/php-fpm \
    && apt-upgrade -uq \
    && cleanup

# adds config files
ADD root/ /

# sets the working directory
# WORKDIR $WEBHOME

# exposes port
EXPOSE 9000

# sets default entrypoint
ENTRYPOINT ["/init"]

################################################################################

# adds MSMTP settings
ENV MSMTP_RELAY_SERVER_HOSTNAME=mailhog
ENV MSMTP_RELAY_SERVER_PORT=1025

# adds more PHP variables for php-fpm
ENV PHP_DEFAULT_CHARSET=UTF-8
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_DISPLAY_STARTUP_ERRORS=0
ENV PHP_ERROR_LOG=/var/log/php-fpm/error.log
ENV PHP_ERROR_REPORTING=22527
ENV PHP_MAX_EXECUTION_TIME=360
ENV PHP_MEMORY_LIMIT=7168M
ENV PHP_POST_MAX_SIZE=1000M
ENV PHP_UPLOAD_MAX_FILE_SIZE=100M
ENV PHP_UPLOAD_MAX_INPUT_VARS=10000

# adds PHP-FPM settings
ENV PHP_POOL_NAME="$PHP_POOL_NAME"
ENV PHPFPM_CONF_DIR=/etc/php/fpm/conf.d
ENV PHPFPM_SOCK_PATH="$PHPFPM_SOCK_PATH"

# adds PHP PM settings
ENV PHP_PM_CONTROL=dynamic
ENV PHP_PM_MAX_CHILDREN=200
ENV PHP_PM_MAX_SPARE_SERVERS=150
ENV PHP_PM_MIN_SPARE_SERVERS=50
ENV PHP_PM_START_SERVERS=50

# adds other Opcache settings
ENV PHP_OPCACHE_INTERNED_STRINGS_BUFFER=200
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=130987
ENV PHP_OPCACHE_MAX_WASTED_PERCENTAGE=15
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=3072
ENV PHP_OPCACHE_PRELOAD=
ENV PHP_OPCACHE_REVALIDATE_FREQ=60
ENV PHP_OPCACHE_REVALIDATE_PATH=0
ENV PHP_OPCACHE_SAVE_COMMENTS=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=1
ENV PHP_OPCACHE_ENABLE_CLI=1

################################################################################