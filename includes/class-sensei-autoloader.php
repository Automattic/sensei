<?php
/**
 * File containing the class Sensei_Autoloader.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // security check, don't load file outside WP.
}

/**
 * Old autoloader for Sensei. No longer used.
 *
 * @package Core
 * @since 1.9.0
 * @deprecated $$next-version$$
 */
class Sensei_Autoloader {
	/**
	 * Generate a list of Sensei class and map them the their respective
	 * files within the includes directory
	 *
	 * @since 1.9.0
	 * @deprecated $$next-version$$
	 */
	public function initialize_class_file_map() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
	}

	/**
	 * Autoload all sensei files as the class names are used.
	 *
	 * @deprecated $$next-version$$
	 */
	public function autoload() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
	}
}
