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
 */
class Sensei_Autoloader_Bundle {
	/**
	 * Path to the includes directory within Sensei.
	 *
	 * @var string
	 */
	private $include_path;

	/**
	 * Sensei_Autoloader_Bundle constructor.
	 *
	 * @param string $bundle_identifier_path  Path relative to includes.
	 */
	public function __construct( $bundle_identifier_path = '' ) {
		// Setup a relative path for the current autoload instance.
		$this->include_path = trailingslashit( trailingslashit( untrailingslashit( __DIR__ ) ) . $bundle_identifier_path );
	}

	/**
	 * Load a class of bundle.
	 *
	 * @param string $class The class name.
	 * @return bool
	 */
	public function load_class( $class ) {

		// Check for file in the main includes directory.
		$class_file_path = $this->include_path . 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		if ( file_exists( $class_file_path ) ) {
			require_once $class_file_path;
			return true;
		}

		// Lastly check legacy types.
		$stripped_woothemes_from_class = str_replace( 'woothemes_', '', strtolower( $class ) ); // Remove woothemes.
		$legacy_class_file_path        = $this->include_path . 'class-' . str_replace( '_', '-', strtolower( $stripped_woothemes_from_class ) ) . '.php';

		if ( file_exists( $legacy_class_file_path ) ) {
			require_once $legacy_class_file_path;
			return true;
		}

		return false;

	}
}
