#!/usr/bin/env bash

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


# Update version in sensei-lms.php
sed -E -i '' "s/\* Version: [0-9]+\.[0-9]+\.[0-9]+/\* Version: $VERSION/" sensei-lms.php

# Update first occurrence of version in package.json & package-lock.json
# doesn't work yet...
sed -i '' "0,/^  \"version\":/s/^  \"version\": \".*\"/  \"version\": \"$VERSION\"/" package.json



