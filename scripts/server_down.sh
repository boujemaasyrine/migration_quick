#!/usr/bin/env bash

USER=$1
crontab -u $USER ../cron_bo_back.txt
service apache2 stop