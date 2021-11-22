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
	 * Directory for the bundle.
	 *
	 * @var string
	 */
	private $directory;

	/**
	 * Sensei_Autoloader_Bundle constructor.
	 *
	 * @param string $bundle_identifier_path Path relative to includes.
	 */
	public function __construct( $bundle_identifier_path = '' ) {

		$this->directory = $bundle_identifier_path;
		// Setup a relative path for the current autoload instance.
		$this->include_path = trailingslashit( trailingslashit( untrailingslashit( __DIR__ ) ) . $bundle_identifier_path );
	}

	/**
	 * Load a class of bundle.
	 *
	 * @param string $class The class name.
	 *
	 * @return bool
	 */
	public function load_class( $class ) {

		$basename  = preg_replace( '/[_\\\]/', '-', strtolower( $class ) );
		$filenames = [ $basename ];

		if ( ! empty( $this->directory ) ) {
			$namespace = 'sensei-' . str_replace( '/', '-', $this->directory ) . '-';

			if ( 0 === strpos( $basename, $namespace ) ) {
				$filenames[] = substr( $basename, strlen( $namespace ) );
			}
		}

		// Legacy woothemes prefixed.
		$filenames[] = str_replace( 'woothemes-', '', $basename );

		foreach ( $filenames as $filename ) {
			$class_file_path = $this->include_path . 'class-' . $filename . '.php';
			if ( file_exists( $class_file_path ) ) {
				require_once $class_file_path;
				return true;
			}
		}

		return false;

	}
}
