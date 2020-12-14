#!/usr/bin/env bash

set -ex

docker-compose -f docker-compose.yaml -f ../../../docker-compose.yaml run --user=xfs --entrypoint='/usr/bin/env' wordpress-cli \
	wp "$@"
