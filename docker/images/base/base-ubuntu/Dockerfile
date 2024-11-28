################################################################################

ARG SCRATCH_IMAGE=scratch
ARG BASE_S6_IMAGE=s6-overlay
ARG BASE_OS_IMAGE=ubuntu:22.04
ARG S6_VERSION="v3.1.6.2"

################################################################################

# override scratch image
FROM ${SCRATCH_IMAGE} as scratch

################################################################################

# s6 source image
FROM ${BASE_S6_IMAGE}:${S6_VERSION} as s6

################################################################################

# base image
FROM ${BASE_OS_IMAGE} as base

# sets defaults
ARG DEBIAN_FRONTEND=noninteractive
ARG BUILD_DEPS="apt-utils"
ARG WEBSSH_USER="root"
ARG WEBSSH_PASS="Docker!"

################################################################################

# adds binaries
ADD root/ /

# copies s6-overlay from source
COPY --from=s6 / /

# updates packages
RUN echo "🤖 Updating packages"
RUN apt-update --fix-missing
RUN apt-install $BUILD_DEPS ca-certificates cron openssl \
    cron telnet dos2unix nano git tar zip unzip openssh-server xz-utils \
    libxrender1 fontconfig libxtst6 xfonts-base libjpeg-turbo8 xfonts-75dpi \
    htop
#SSH
RUN mkdir /var/run/sshd
RUN usermod -p "$(openssl passwd -6 $WEBSSH_PASS)" $WEBSSH_USER
EXPOSE 2222
#WKHTMLTOPDF
RUN dpkg -i /wkhtmltox.deb && \
    rm /wkhtmltox.deb
#NODEJS
RUN tar -xJf /nodejs.tar.xz -C /usr/local --strip-components=1 && \
    npm i -g npm@6.9.0 && \
    npm install -g gulp@3.9.1 gulp-cli bower@1.8.8 && \
    rm /nodejs.tar.xz
RUN ln -sf /etc/ssl/certs/ca-certificates.crt /usr/lib/ssl/cert.pem
RUN disable cron
RUN apt-upgrade -uq
RUN cleanup $BUILD_DEPS

################################################################################

# the main image
FROM  scratch
SHELL ["/bin/bash", "-c"]

# sets defaults
ARG WEBUSER=www-data
ARG WEBGROUP=www-data

# copies from from build stage
COPY --from=base / /

# sets the working directory
WORKDIR /root

# sets default command
CMD ["/bin/bash"]

################################################################################

# S6 variables
ARG S6_VERSION="v3.1.6.2"
ENV S6_BEHAVIOUR_IF_STAGE2_FAILS=2
ENV S6_CMD_WAIT_FOR_SERVICES_MAXTIME=600000
ENV S6_KEEP_ENV=1
ENV S6_KILL_FINISH_MAXTIME=3000
ENV S6_KILL_GRACETIME=3000
ENV S6_LOGGING=0
ENV S6_SERVICES_GRACETIME=3000
ENV S6_VERBOSITY=1
ENV S6_VERSION=$S6_VERSION

# sets WEBUSER and WEBGROUP
ENV WEBUSER="$WEBUSER"
ENV WEBGROUP="$WEBGROUP"
ENV WEBHOME="/var/www/html"

# adds OS variables
ENV DEBUG_MODE=false
ENV ENABLE_CRONTAB=true
ENV HOME=/root
ENV LANG=C.UTF-8
ENV LANGUAGE=C.UTF-8
ENV LC_ALL=C
ENV PATH=/command:/usr/local/bin:/usr/local/sbin:/usr/bin:/usr/sbin:/bin:/sbin
ENV SHELL=/bin/bash
ENV TERM=xterm

################################################################################