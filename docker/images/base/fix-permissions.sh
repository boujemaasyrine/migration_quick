#!/bin/bash

echo "Fixing permissions..."

chmod -R 755 docker/images/base

chmod -R +x docker/images/base/base-ubuntu/root/etc/cont-init.d/ 
chmod -R +x docker/images/base/base-ubuntu/root/etc/services.d/ 
chmod -R +x docker/images/base/base-ubuntu/root/usr/bin/

chmod -R +x docker/images/base/php/cli/root/startup/
chmod -R +x docker/images/base/php/cli/root/usr/bin/

chmod -R +x docker/images/base/php/fpm/root/etc/cont-init.d/
chmod -R +x docker/images/base/php/fpm/root/etc/services.d/
chmod -R +x docker/images/base/php/fpm/root/usr/bin/

chmod -R +x docker/images/base/servers/fpm-apache/root/etc/cont-init.d/
chmod -R +x docker/images/base/servers/fpm-apache/root/etc/services.d/
chmod -R +x docker/images/base/servers/fpm-apache/root/usr/bin/