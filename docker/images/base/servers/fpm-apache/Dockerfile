################################################################################

ARG BASE_IMAGE=php
ARG PHP_VERSION=5.6

################################################################################

# base image
FROM ${BASE_IMAGE}:${PHP_VERSION}-fpm

ARG APACHE_BUILD_DEPS="gnupg2 apt-utils"
ARG APACHE_PPA_KEY="4F4EA0AAE5267A6C"
ARG APACHE_PPA_URL="http://ppa.launchpadcontent.net/ondrej/apache2/ubuntu"

################################################################################

# installs apache
RUN echo "Installing Apache" \
    && apt-update \
    && apt-install $APACHE_BUILD_DEPS \
    && apt-deb ppa-apache ${APACHE_PPA_URL} ${APACHE_PPA_KEY} \
    && apt-install apache2 && disable apache2 \
    && echo "ServerName localhost" >>/etc/apache2/apache2.conf \
    && sed  -i 's/LogFormat "%h %l %u %t \\\"%r\\\" %>s %O \\\"/LogFormat "%a %l %u %t \\\"%r\\\" %>s %O \\\"/' /etc/apache2/apache2.conf \
    && grep -lr "unix" /etc/apache2/ | xargs -I {} sed -i 's#unix:.*fpm.sock#unix:/var/run/php/php-fpm.sock#g' {} \
    && echo >>/etc/apache2/envvars \
    && echo "export DOCUMENT_ROOT=/var/www/html/web" >>/etc/apache2/envvars \
    && echo "export APACHE_RUN_USER=www-data" >>/etc/apache2/envvars \
    && echo "export APACHE_RUN_GROUP=www-data" >>/etc/apache2/envvars \
    \
    # modifies Apache modules
    && a2dismod \
        mpm_prefork mpm_worker \
    && a2enmod \
        actions autoindex brotli cache deflate expires headers http2 \
        mpm_event proxy proxy_fcgi remoteip rewrite setenvif ssl unique_id \
    && a2enconf php* \
    \
    # cleanup
    && rm -rf /var/log/apache2 ${WEBHOME}/*.html \
    && apt-upgrade -uq \
    && cleanup $APACHE_BUILD_DEPS

# adds config files
ADD root/ /

# sets the working directory
# WORKDIR $WEBHOME

# exposes ports
EXPOSE 80
EXPOSE 443
EXPOSE 443/udp

# sets default entrypoint
ENTRYPOINT ["/init"]

################################################################################

# sets env variables
ENV APACHE_DOCUMENT_ROOT="/web"
ENV FALLBACK_INDEX_FILE="app.php"

# automatically create index.php
ENV AUTO_CREATE_INDEX_FILE="false"

################################################################################

LABEL traefik.enable=true