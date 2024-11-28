#!/usr/bin/env bash
if [ ! -d "app/cache" ]; then
mkdir app/cache
fi

if [ ! -d "app/logs" ]; then
mkdir app/logs
fi

if [ ! -d "data/po_xml" ]; then
mkdir data/po_xml
fi

if [ ! -d "data/tmp" ]; then
mkdir data/tmp
fi

if [ ! -d "web/uploads" ]; then
mkdir web/uploads
fi

chown -R $1:www-data ./
chmod 775 -R app/cache
chmod 775 -R app/logs
chmod 775 -R data/po_xml
chmod 775 -R data/tmp
chmod 775 -R web/uploads