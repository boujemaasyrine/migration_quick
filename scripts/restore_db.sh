#!/usr/bin/env bash
DB_USERNAME="postgresadmin"
DB_PASSWORD="p0stgresadm1n"
DB_HOST="localhost"
DB_NAME="pre_prod_bo"
DIR_NAME=$(dirname $0)

if [ -d $DIR_NAME"/../db_dumps" ]; then
    cd $DIR_NAME"/../db_dumps"
    DATE_STR=$(date +"%Y%m%d")
    DUMP_NAME="BO_QUICK_DUMPR_"$DATE_STR"*.dump"
    FILE=$(find $DUMP_NAME -print -quit)
    export PGPASSWORD=$DB_PASSWORD
    pg_restore -U $DB_USERNAME -n public -c -1 -h $DB_HOST -d $DB_NAME $FILE
    unset PGPASSWORD
fi
