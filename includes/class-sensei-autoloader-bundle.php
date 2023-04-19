<?php
/**
 * File containing the class Sensei_Autoloader_Bundle.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // security check, don't load file outside WP.
}

/**
 * A Sensei_Autoloader_Bundle is a directory of which all classes are going to be loaded.
 *
 * This class is no longer used.
 *
 * @deprecated 4.13.1
 */
class Sensei_Autoloader_Bundle {
	/**
	 * Load a class of bundle.
	 *
	 * @deprecated 4.13.1
	 *
	 * @return bool
	 */
	public function load_class() {
		_deprecated_function( __METHOD__, '4.13.1' );

		return false;
	}
}
