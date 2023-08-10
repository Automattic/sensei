#!/usr/bin/env bash

# Exit on error and output commands.
set -ex

CURRENT_DIR=$(pwd)
OS_TYPE=$(uname -s)
# sed arguments in different OS passed a bit differently.
if [[ "$OS_TYPE" == "Darwin" ]]; then
	sed -E -i '' "s/^.* \[#([0-9]+)\]$/&(https:\/\/github.com\/Automattic\/sensei\/pull\/\\1)/" "$CURRENT_DIR/changelog.txt"
elif [[ "$OS_TYPE" == "Linux" ]]; then
	# You are on Linux
	sed -E -i'' "s/^.* \[#([0-9]+)\]$/&(https:\/\/github.com\/Automattic\/sensei\/pull\/\\1)/" "$CURRENT_DIR/changelog.txt"
else
    echo "Unsupported operating system"
fi



