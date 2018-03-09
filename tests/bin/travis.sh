#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# install php-coveralls to send coverage info
	rm composer.json
	rm composer.lock
	composer init --require=satooshi/php-coveralls:0.7.0 -n
	composer install --no-interaction

elif [ $1 == 'after' ]; then

	# no Xdebug and therefore no coverage in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# send coverage data to coveralls
	php vendor/bin/coveralls --verbose --exclude-no-stmt

	# get scrutinizer ocular and run it
	wget https://scrutinizer-ci.com/ocular.phar
	ocular.phar code-coverage:upload --format=php-clover ./clover.xml

fi
