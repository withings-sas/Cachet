#!/bin/bash

set -x
set -e

BUILD_SERVER=fr-hq-build-02.corp.withings.com
BUILD_USER=scaleweb
BUILD_PATH=/home/$BUILD_USER/Cachet

ENVIRONMENT=$1
COMMIT=$2

# Sync source
rsync -az --delete --exclude="build/" ./ $BUILD_USER@$BUILD_SERVER:$BUILD_PATH/

# Build
#ssh $BUILD_USER@$BUILD_SERVER "cd $BUILD_PATH; npm install"
#ssh $BUILD_USER@$BUILD_SERVER "cd $BUILD_PATH; grunt --env=$ENVIRONMENT --dest-site=dist --dest-assets=assets"

# Retrieve build
if [ -d build ]; then
  rm -rf build
fi
mkdir build
#rsync -az $BUILD_USER@$BUILD_SERVER:$BUILD_PATH/ build/

