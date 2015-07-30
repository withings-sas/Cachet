#!/bin/bash

set -x
set -e

BUILD_SERVER=fr-hq-build-02.corp.withings.com
BUILD_USER=scaleweb
BUILD_PATH=/home/$BUILD_USER/Cachet

ENVIRONMENT=$1
COMMIT=$2

# Sync source
rsync -az --delete --exclude="vendor/" --exclude="composer.phar" ./ $BUILD_USER@$BUILD_SERVER:$BUILD_PATH/

# Build
ssh $BUILD_USER@$BUILD_SERVER "cd $BUILD_PATH; [[ -f composer.phar ]] || curl -sS https://getcomposer.org/installer | php"
ssh $BUILD_USER@$BUILD_SERVER "cd $BUILD_PATH; ./composer.phar install --no-dev -o"
ssh $BUILD_USER@$BUILD_SERVER "cd $BUILD_PATH; ./composer.phar update --no-dev"

# Retrieve build
#if [ -d build ]; then
#  rm -rf build
#fi
#mkdir build
rsync -az --delete $BUILD_USER@$BUILD_SERVER:$BUILD_PATH/vendor/ vendor/

