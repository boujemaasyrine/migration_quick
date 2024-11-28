#!/usr/bin/env bash
DIR_NAME=$(dirname $0)

cd $DIR_NAME"/../data"

DIR_TMP="./tmp"
DIR_PO_XML="./po_xml"

echo dir_tmp $DIR_TMP
echo dir_PO $DIR_PO_XML

if [ "$(ls -A $DIR_TMP)" ]; then
echo "DELETING $DIR_TMP"
cd $DIR_TMP
find * -mtime +7 -exec rm {} \;
#rm *
cd ..
else
echo "DIRECTORY $DIR_TMP IS EMPTY"
fi


if [ "$(ls -A $DIR_PO_XML)" ]; then
echo "DELETING $DIR_PO_XML"
cd $DIR_PO_XML
find * -mtime +7 -exec rm {} \;
#rm *
cd ..
else
echo "DIRECTORY $DIR_PO_XML IS EMPTY"
fi
