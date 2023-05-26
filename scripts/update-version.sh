#!/usr/bin/env bash

set -ex

# Check version provided
VERSION=$1
if [ -z "$VERSION" ]; then
    echo "No version provided."
    exit 1
fi

if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Version provided is not a valid SemVer version."
    exit 1
fi

CURRENT_DIR=$(pwd)

# Update version in sensei-lms.php
sed -E -i'' "s/\* Version: [0-9]+\.[0-9]+\.[0-9]+/\* Version: $VERSION/" "$CURRENT_DIR/sensei-lms.php"

# Update first occurrence of version in package.json & package-lock.json
sed -i'' "s/^  \"version\": \"[0-9]*\.[0-9]*\.[0-9]*\"/  \"version\": \"$VERSION\"/g" "$CURRENT_DIR/package.json"
sed -i'' "s/^  \"version\": \"[0-9]*\.[0-9]*\.[0-9]*\"/  \"version\": \"$VERSION\"/g" "$CURRENT_DIR/package-lock.json"

