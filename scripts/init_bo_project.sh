#!/usr/bin/env bash
BO_BELUX_REMOTE_REPO=$1
mkdir quick_bo_belux
cd quick_bo_belux
git init
git remote add origin $BO_BELUX_REMOTE_REPO
git pull origin master