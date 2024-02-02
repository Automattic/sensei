#!/usr/bin/env bash

# Exit on error and output commands.
set -ex

NEXT_VERSION=$1
if [ "$NEXT_VERSION" = "" ]; then
	echo "Error: Version not set!"
	exit
fi

RELEASE_BRANCH=$2
if [ "$RELEASE_BRANCH" = "" ]; then
	echo "Error: Release branch not set!"
	exit
fi

# Fix permissions for ssh config and keys.
chown -R root:root /root/.ssh
export GIT_SSH_COMMAND="ssh -i /root/.ssh/id_rsa"

# Create working directory and checkout Sensei.
mkdir /release && cd /release
git clone git@github.com:Automattic/sensei.git
cd /release/sensei

# Checkout release branch.
git switch $RELEASE_BRANCH

# Exit if could not switch to release branch.
if [ $? -ne 0 ]; then
	echo "Error: Could not switch to release branch!"
	exit
fi

# Install dependencies.
composer install && npm ci

# Disable commit signing.
git config --global commit.gpgsign false

# Create the release branch.
git switch -c "release/$NEXT_VERSION"

echo "Replace next version tag"
./scripts/replace-next-version-tag.sh $NEXT_VERSION
if [[ -n $(git status -s) ]]; then
	git add .
	git commit -m 'Replace next version tag'
else
  echo "There are no changes after next version tag replacement."
fi

echo "Update plugin version"
update-version.sh $NEXT_VERSION
if [[ -n $(git status -s) ]]; then
	git add .
	git commit -m 'Update plugin version'
else
  echo "There are no changes after updating the version in plugin files."
fi

echo "Changlogger write"
# Write changelog.
composer exec -- changelogger write --add-pr-num
# Unwrap PR links.
changelog-unwrap-pr-links.sh
# Copy changelog section in readme.
./scripts/copy-changelog-to-readme.php

if [[ -n $(git status -s) ]]; then
	git add .
	git commit -m 'Update chaneglog'
else
  echo "There are no changes after running changelogger."
fi

echo "Build translations"
npm run i18n:build -- --allow-root
if [[ -n $(git status -s) ]]; then
	git add .
	git commit -m 'Update translations'
else
	echo "There are no changes after building translations."
fi

# Push all changes to GitHub
git push --set-upstream origin "release/$NEXT_VERSION"

# Login to create PR.
gh auth login

# Create PR.
gh pr create --assignee @me --base trunk --draft --title "Release $NEXT_VERSION" --reviewer Automattic/nexus --label "No Changelog"
