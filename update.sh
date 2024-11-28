#!/usr/bin/env bash

git config core.fileMode false
git fetch --all
git reset --hard origin/master
