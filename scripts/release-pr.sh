#!/usr/bin/env bash

# Output commands.
set -x

NEXT_VERSION=$1
if [ "$NEXT_VERSION" = "" ]; then
	echo "Error: Version not set"
	exit
fi

RELEASE_BRANCH=$2
if [ "$RELEASE_BRANCH" = "" ]; then
	RELEASE_BRANCH="trunk"
fi

echo "Preparing release/$NEXT_VERSION from $RELEASE_BRANCH"

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
echo $SCRIPT_DIR

# Dangerous! Considered as non-secure way of sharing keys!
# TODO: https://www.fastruby.io/blog/docker/docker-ssh-keys.html
cp -r ~/.ssh "$SCRIPT_DIR/ssh-data"

docker build -t release-build "$SCRIPT_DIR" &&  \
	docker run --name release-steps --rm -it \
	-v ~/.gitconfig:/etc/gitconfig \
	release-build \
	bash -c "/usr/bin/release-pr-steps.sh $NEXT_VERSION $RELEASE_BRANCH"

docker image rm release-build
rm -rf "$SCRIPT_DIR/ssh-data"

