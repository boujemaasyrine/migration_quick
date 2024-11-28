#!/usr/bin/env bash
DB_USERNAME="quick_user_db"
DB_PASSWORD="quick_user_db_pw"
DB_HOST="localhost"
DB_NAME="saas_quick"
DIR_NAME=$(dirname $0)


if [ ! -d $DIR_NAME"/../db_dumps" ]; then
mkdir ./../db_dumps
fi

cd $DIR_NAME"/../db_dumps"

DATE_STR=$(date +"%Y%m%d%H%M%S")
DUMP_NAME="SAAS_QUICK_DUMP_"$DATE_STR".dump"
export PGPASSWORD=$DB_PASSWORD
pg_dump -U $DB_USERNAME -h $DB_HOST -Fc -d $DB_NAME > $DUMP_NAME
unset PGPASSWORD
if [ -f $DUMP_NAME ];
then
    mv $DUMP_NAME  "new."$DUMP_NAME
    rm SAAS_QUICK_DUMP_*
    mv "new."$DUMP_NAME $DUMP_NAME
fi
