#!/usr/bin/env bash

if [[ ${TRAVIS_PHP_VERSION} > 7.1 ]]; then
	composer install
fi

if [[ ! -z "$WP_VERSION" ]]; then
	phpunit
fi
