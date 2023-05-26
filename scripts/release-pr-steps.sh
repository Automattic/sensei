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
composer install && npm install

# Disable commit signing.
git config --global commit.gpgsign false

# Create the release branch.
git switch -c "release/$NEXT_VERSION"

echo "Replace next version tag"
./scripts/replace-next-version-tag.sh $NEXT_VERSION
git add .
git commit -m 'Replace next version tag'

echo "Update plugin version"
update-version.sh $NEXT_VERSION
git add .
git commit -m 'Update plugin version'

echo "Changlogger write"
composer exec -- changelogger write
git add .
git commit -m 'Update chaneglog'

echo "Build translations"
npm run build:assets && wp i18n make-pot --exclude=build,lib,vendor,node_modules,assets/vendor --headers='{"Last-Translator":null,"Language-Team":null,"Report-Msgid-Bugs-To":"https://wordpress.org/support/plugin/sensei-lms"}' . lang/sensei-lms.pot --allow-root
git add .
git commit -m 'Update translations'

# Push all changes to GitHub
git push --set-upstream origin "release/$NEXT_VERSION"

# Login to create PR.
gh auth login

# Create PR.
gh pr create --assignee @me --base trunk --draft --title "Release $NEXT_VERSION" --reviewer Automattic/nexus --label "[Type] Maintenance"
