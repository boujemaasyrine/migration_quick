#!/usr/bin/env bash
REMOTE_SERVER=$1
REMOTE_PROJECT_PATH=$2
RESTAURANT_CODE=$3
SAAS_PROJECT_PATH=$4
LOG_FILE="/home/adminsup/restaurant_migration_log.log"
#check if all needed parameters are passed to the script
now=$(date +"%T")
echo "Import Script start at : $now" > $LOG_FILE
if [ $# -lt 4 ]; then
    echo "Please provide all 4 arguments, your command line contains only $# arguments." >> $LOG_FILE
    exit 1
fi

if [ "$REMOTE_SERVER" = "" ]; then
    echo "No remote server provided!" >> $LOG_FILE
    exit 1
fi

if [ "$REMOTE_PROJECT_PATH" = "" ]; then
     echo "No remote project path provided!" >> $LOG_FILE
     exit 1
fi

if [ "$SAAS_PROJECT_PATH" = "" ]; then
     echo "No local project path provided!" >> $LOG_FILE
     exit 1
fi

if [ "$RESTAURANT_CODE" = "" ]; then
   echo "No remote password provided!" >> $LOG_FILE
   exit 1
fi

##all parameters are passed, so connect to remote server and begin bo export

ssh -o StrictHostKeyChecking=no $REMOTE_SERVER php $REMOTE_PROJECT_PATH/app/console bo:export:all --env=prod >> $LOG_FILE
now=$(date +"%T")
if [ $? != 0 ]; then
  echo "Export failed on remote server! Exit Script at : $now" >> $LOG_FILE
  exit 1
else
  echo "Export complete at : $now" >> $LOG_FILE
fi

#export ended successfully, dowload exported files via sftp
sftp -oCompression=yes $REMOTE_SERVER:$REMOTE_PROJECT_PATH/data/export/saas/* $SAAS_PROJECT_PATH/data/import/saas
now=$(date +"%T")
if [ $? != 0 ]; then
  echo "Transfer failed! Exit Script at : $now" >> $LOG_FILE
  exit 1
else
  echo "Transfer complete at : $now" >> $LOG_FILE
fi

#start importing data to saas
php $SAAS_PROJECT_PATH/app/console saas:import:restaurant $RESTAURANT_CODE --env=prod >> $LOG_FILE
php $SAAS_PROJECT_PATH/app/console quick:sync:execute --env=prod
#php $SAAS_PROJECT_PATH/app/console saas:auto:import:restaurant --restaurant=$RESTAURANT_CODE --skip-restaurant-creation=1 --env=prod >> $LOG_FILE

now=$(date +"%T")
echo "Script ended at : $now" >> $LOG_FILE

