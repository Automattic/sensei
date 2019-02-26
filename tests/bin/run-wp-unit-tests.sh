#!/usr/bin/env bash

if [[ ! -z "$WP_VERSION" ]]; then

	if phpunit; then
		# Everything is fine
		:
	else
		exit 1
	fi

fi
