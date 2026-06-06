# Sensei Unit Tests

## Setup

### Using wp-env (Recommended)

[wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) provides a Docker-based WordPress environment with MySQL and the WordPress test suite pre-configured. It is the easiest way to run the tests.

#### Prerequisites

1. [Docker](https://docs.docker.com/get-docker/) installed and running.
2. [Composer](https://getcomposer.org/) installed.
3. Run `composer install` in the plugin root directory.
4. Run `npm install` in the plugin root directory.

#### Start the environment

    $ npm run wp-env start

### Using a local MySQL database

If you prefer not to use wp-env, you can run the tests against a local MySQL database.

#### Prerequisites

1. [Composer](https://getcomposer.org/).
2. [svn](https://subversion.apache.org/packages.html) (needed by the install script).
3. A MySQL database. Do not use an existing database or you will lose data.

You can install MySQL via [Docker](https://docs.docker.com/get-docker/):

    $ docker run --name mysql_57 -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=<test_db_name> -e MYSQL_USER=<test_user_name> -e MYSQL_PASSWORD=<test_user_password> --rm -d mysql:5.7

Or install [MySQL Server](https://dev.mysql.com/doc/refman/8.0/en/installing.html) directly.

#### Install WP test suite

Run `composer install` and `npm install` in the plugin root directory. Then install the WP test suite:

If using Docker for MySQL:

    $ TMPDIR=/tmp ./tests/bin/install-wp-tests.sh <test_db_name> <test_user_name> <test_user_password> 127.0.0.1 latest true

If using a local MySQL Server:

    $ TMPDIR=/tmp ./tests/bin/install-wp-tests.sh <test_db_name> <test_user_name> <test_user_password>

## Running Tests

### With wp-env

Run all PHP tests:

    $ npm run test-php:wp-env

Run a specific test class:

    $ npm run test-php:wp-env -- --filter Sensei_Class_Admin_Test

Run all Jest tests:

    $ npm run test-js

### With a local MySQL database

Run all PHP tests:

    $ npm run test-php

Run a specific test class:

    $ npm run test-php -- --filter Sensei_Class_Admin_Test

Run all Jest tests:

    $ npm run test-js

## Writing Tests

* Each test file should roughly correspond to an associated source file, e.g. the `test-class-woothemes-sensei.php` test file covers code in `class-woothemes-sensei.php`.
* Each test method should cover a single method or function with one or more assertions.
* A single method or function can have multiple associated test methods if it's a large or complex method.
* Prefer `assertSame()` where possible as it tests both type & equality.
* Remember that only methods prefixed with `test` will be run.
* Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
