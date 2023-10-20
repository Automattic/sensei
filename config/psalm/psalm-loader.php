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

if ( ! defined( 'SENSEI_LMS_PLUGIN_PATH' ) ) {
	define( 'SENSEI_LMS_PLUGIN_PATH', ABSPATH );
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
require_once __DIR__ . '/../../vendor/woocommerce/action-scheduler/functions.php';
