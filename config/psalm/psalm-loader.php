<?php
/**
 * File containing the psalm loader.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
	define( 'ABSPATH', __DIR__ . '/../../' );
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
