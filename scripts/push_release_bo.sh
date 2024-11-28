#!/usr/bin/env bash
DATE_STR=$(date +"%Y%m%d%H%I%M%S")
BO_REPO=$1
mkdir src_for_push
cd src_for_push
echo "Initialing git"
git init
git remote add origin $BO_REPO
echo "Pulling data"
git pull origin master
echo "Deleting old data"
find . -maxdepth 1 -not -name ".git" -not -name "." -not -name ".." -exec rm -rf {} \;
echo "Copying data"
cp -r ../source/* ./
git add .
echo "Committing new data"
git commit -m "release_"$DATE_STR
echo "Pushing new data"
git push origin master