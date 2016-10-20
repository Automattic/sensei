# Sensei Unit Tests

## Setup

### Using Varying Vagrant Vagrants

1) `$ vagrant SSH` into the machine running your Sensei setup.

2) `$ cd /srv/www/wp-trunk ` or `$ cd /srv/www/wp-trunk wp-develop` depending on where you run Sensei from.

3) Proceed to `cd wp-content/plugins/sensei/` or to directory where you've installed Sensei

4) Install the tests:

    $ tests/bin/install_vvv.sh

### In your local machine

1) Install [PHPUnit](http://phpunit.de/) by following their [installation guide](https://phpunit.de/getting-started.html). If you've installed it correctly, this should display the version:

    $ phpunit --version


2) Install WordPress and the WP Unit Test lib using the `install.sh` script. Change to the plugin root directory and type:

    $ tests/bin/install.sh

**Important**: You might need to change the DB parameters accordingly within the `install.sh` file.

## Running Tests

Simply change to the plugin root directory and type:

    $ phpunit

The tests will execute and you'll be presented with a summary. Code coverage documentation is automatically generated as HTML in the `tmp/coverage` directory.

You can run specific tests by providing the path and filename to the test class:

    $ phpunit tests/unit-tests/api/webhooks

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ phpunit --coverage-text

## Writing Tests

* Each test file should roughly correspond to an associated source file, e.g. the `test-class-woothemes-sensei.php` test file covers code in `class-woothemes-sensei.php`
* Each test method should cover a single method or function with one or more assertions
* A single method or function can have multiple associated test methods if it's a large or complex method
* Prefer `assertsEquals()` where possible as it tests both type & equality
* Remember that only methods prefixed with `test` will be run.
* Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
