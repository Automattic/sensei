#!/usr/bin/env bash

if [[ ! -z "$WP_VERSION" ]]; then
	phpunit
	WP_MULTISITE=1 phpunit
fi
