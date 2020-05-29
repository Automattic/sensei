#!/usr/bin/env bash

# Check for required version
WPCLI_VERSION=`wp cli version | cut -f2 -d' '`
if [ ${WPCLI_VERSION:0:1} -lt "2" -o ${WPCLI_VERSION:0:1} -eq "2" -a ${WPCLI_VERSION:2:1} -lt "1" ]; then
	echo WP-CLI version 2.1.0 or greater is required to make JSON translation files
	exit
fi

# Substitute JS source references with build references
# For new rules, duplicate the line `-e ...`
for T in `find lang -name "*.po"`
	do
		sed \
			-e 's/ assets\/onboarding[^:]*:/ assets\/dist\/onboarding\/index.js:/gp' \
			$T | uniq > $T-build
		rm $T
		mv $T-build $T
	done

# Make the JSON files
wp i18n make-json lang --no-purge
