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

# Check if jq is installed. We use it to update the version in package.json and package-lock.json
if ! command -v "jq" >/dev/null 2>&1; then
    echo "jq is not installed. https://jqlang.github.io/jq/download/"
fi

CURRENT_DIR=$(pwd)
OS_TYPE=$(uname -s)
# sed arguments in different OS passed a bit differently.
if [[ "$OS_TYPE" == "Darwin" ]]; then
    # You are on macOS

	# Update version in sensei-lms.php
	sed -E -i '' "s/\* Version: .*/\* Version: $VERSION/" "$CURRENT_DIR/sensei-lms.php"

	# Find constant and replace the version in sensei-lms.php:
	#	define( 'SENSEI_LMS_VERSION', '4.15.0' ); // WRCS: DEFINED_VERSION.
	sed -E -i '' "s/'SENSEI_LMS_VERSION', '[^']*'/'SENSEI_LMS_VERSION', '$VERSION'/" "$CURRENT_DIR/sensei-lms.php"

	# Update version in the Stable Tag comment in readme.txt
	# Stable tag: 4.15.0
	sed -E -i '' "s/^Stable tag: [0-9]+\.[0-9]+\.[0-9]+/Stable tag: $VERSION/" "$CURRENT_DIR/readme.txt"
elif [[ "$OS_TYPE" == "Linux" ]]; then
	# You are on Linux

	# Update version in sensei-lms.php
	sed -E -i'' "s/\* Version: .*/\* Version: $VERSION/" "$CURRENT_DIR/sensei-lms.php"

	# Find constant and replace the version in sensei-lms.php:
	#	define( 'SENSEI_LMS_VERSION', '4.15.0' ); // WRCS: DEFINED_VERSION.
	sed -E -i'' "s/'SENSEI_LMS_VERSION', '[^']*'/'SENSEI_LMS_VERSION', '$VERSION'/" "$CURRENT_DIR/sensei-lms.php"

	# Update version in the Stable Tag comment in readme.txt
	# Stable tag: 4.15.0
	sed -E -i'' "s/^Stable tag: [0-9]+\.[0-9]+\.[0-9]+/Stable tag: $VERSION/" "$CURRENT_DIR/readme.txt"
else
    echo "Unsupported operating system"
fi

# Update package.json.
jq ".version = \"$VERSION\"" "$CURRENT_DIR/package.json" > "$CURRENT_DIR/package.json.tmp" && \
	mv "$CURRENT_DIR/package.json.tmp" "$CURRENT_DIR/package.json"

# Update package-lock.json: the first occurrence of version in the root object.
jq ".version = \"$VERSION\"" "$CURRENT_DIR/package-lock.json" > "$CURRENT_DIR/package-lock.json.tmp" && \
	mv "$CURRENT_DIR/package-lock.json.tmp" "$CURRENT_DIR/package-lock.json"

# Update package-lock.json: the second occurrence of version.
jq ".packages.\"\".version = \"$VERSION\"" "$CURRENT_DIR/package-lock.json" > "$CURRENT_DIR/package-lock.json.tmp" && \
	mv "$CURRENT_DIR/package-lock.json.tmp" "$CURRENT_DIR/package-lock.json"
