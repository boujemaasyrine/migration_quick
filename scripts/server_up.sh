#!/usr/bin/env bash

USER=$1;

echo "Start Postgresql server..\n"
service postgresql start

echo "Restore database..\n"
sh ./restore_db.sh

echo "Start Apache server..\n"
service apache2 start

echo "Restore crontab...\n"
crontab -u $USER ../cron_bo.txt
