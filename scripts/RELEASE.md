# Release

Here, we have 2 scripts and one Dockerfile that help preparing the release PR.

## scripts/release-pr.sh 

Run it from the repository's root: `./scripts/release-pr.sh X.Y.Z`, where X.Y.Z is the version you're releasing.

If you want to create the release not from `trunk`, specify the branch name:
```
./scripts/release-pr.sh X.Y.Z feature/branch
```

The script assumes, you have:

- ~/.ssh - directory that contain all needed SSH configuration and keys for GitHub.
- ~/.gitconfig - your configuration for Git. 

This script creates a copy of your SSH directory. This approach is not ideal and is considered as non-secure.
The script removes all the data in the end, so, hopefully, it won't cause damage. 

However, use cautiously: don't share your Docker image or tempory directory/data.

## scripts/release-pr-steps.sh

This script is being run from inside the Docker container and runs all the needed steps:

- Checkout Sensei repository.
- Install dependencies.
- Create a local release branch.
- Replace the next version tag.
- Update changelog.
- Build POT file.
- Push all the changes to GitHub.
- Create the release PR.

## scripts/Dockerfile

Describes the image for Docker, containing:

- PHP 7.4
- Node 18.x
- composer 2
- GitHub CLI 
- WordPress CLI

It also copies the ssh data directory. Once again: this approach is not ideal and is considered as non-secure.

