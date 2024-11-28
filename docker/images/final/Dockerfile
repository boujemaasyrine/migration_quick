################################################################################

ARG BASE_IMAGE=phpfpm-apache
ARG BASE_GIT_COMMIT

################################################################################

FROM ${BASE_IMAGE}:php5.6-${BASE_GIT_COMMIT} as base

ARG APP_VERSION
ARG ENV
ARG WORKSPACE
ARG ASSETS_PATH=docker/images/final/root
ARG CRON_LOG_PATH=/var/log/cron

################################################################################

COPY . $WEBHOME
COPY $ASSETS_PATH/entrypoint /startup/entrypoint

#############################|  P E R M I S S I O N S |#########################

#Give permission to execute cron jobs
RUN chmod 0644 /etc/cron.d/crontab 

#Give permission to store data [CSV, PDF, XLS] under data/tmp
RUN chmod 755 $WEBHOME/data/tmp
RUN chown www-data:www-data $WEBHOME/data/tmp

#Give write permission under data/po_xml
RUN chmod 755 $WEBHOME/data/po_xml
RUN chown www-data:www-data $WEBHOME/data/po_xml

#Give write permission in web/uploads
RUN chmod 755 $WEBHOME/web/uploads
RUN chown www-data:www-data $WEBHOME/web/uploads

#Give write permission in app/ (dev cache)
RUN chmod 755 $WEBHOME/app
RUN chown www-data:www-data $WEBHOME/app

#####################| W O R K S P A C E _ C O N F I G |#######################

RUN cp -rf $ASSETS_PATH/images/$WORKSPACE/*     $WEBHOME/web/src/images/
RUN cp -rf $ASSETS_PATH/images/$WORKSPACE/favicon.*  $WEBHOME/web/
RUN cp -rf $ASSETS_PATH/parameters/$WORKSPACE/* $WEBHOME/app/config/
RUN cp -rf $ASSETS_PATH/crons/$WORKSPACE/$ENV/crontab /etc/cron.d/crontab

################################################################################

RUN crontab /etc/cron.d/crontab

##############################| D E V _ I M A G E |#############################

FROM base as bo-dev

ENV DEBUG_MODE true
ENV PHP_DISPLAY_ERRORS true
ENV FALLBACK_INDEX_FILE app_dev.php
ENV PHP_DISPLAY_STARTUP_ERRORS true
ENV CONFIG_PATH=$WEBHOME/app/config/parameters_dev.yml

RUN mkdir -p $CRON_LOG_PATH/dev
RUN sed -i -e "s/app_version:\s*.*/app_version: ${APP_VERSION}-dev/" $CONFIG_PATH

##############################| P R O D _ I M A G E |#############################

FROM base as bo-prod

ENV DEBUG_MODE false
ENV PHP_DISPLAY_ERRORS false
ENV FALLBACK_INDEX_FILE app.php
ENV PHP_DISPLAY_STARTUP_ERRORS false
ENV CONFIG_PATH=$WEBHOME/app/config/parameters.yml

RUN mkdir -p $CRON_LOG_PATH/prod
RUN sed -i -e "s/app_version:\s*.*/app_version: ${APP_VERSION}/" $CONFIG_PATH

################################################################################