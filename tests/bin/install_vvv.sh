#!/usr/bin/env bash

DB_NAME='wordpress_unit_tests'
DB_USER='root'
DB_PASS='root'
DB_HOST='localhost'
WP_VERSION=${1-latest}

#setup test and core directories
WP_TESTS_DIR=${WP_TESTS_DIR-/srv/www/wordpress-develop/tests/phpunit}
WP_CORE_DIR=${WP_CORE_DIR-/srv/www/wordpress-develop/src/}

set -ex

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	cd $WP_TESTS_DIR
	rm -rf wp-tests-config.php
	cp ../../wp-tests-config.php wp-tests-config.php

	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_db() {
	#setup wp-content dp.php
	echo "<?php if ( ! defined( 'WP_USE_EXT_MYSQL' ) ){ define( 'WP_USE_EXT_MYSQL', false ); }" > $WP_CORE_DIR/wp-content/db.php
}

## Run the above functions

install_test_suite
install_db

## end run