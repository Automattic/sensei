<?php
/**
 * File containing the Sensei_Course_Video_Blocks_Vimeo_Extension class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extends standard Embed block with Vimeo specific functionality for video course progression
 *
 * @since 3.15.0
 *
 * @deprecated 4.6.4
 */
class Sensei_Course_Video_Blocks_Vimeo_Extension extends Sensei_Course_Video_Blocks_Embed_Extension {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Returns an instance of the class.
	 *
	 * @deprecated 4.6.4
	 *
	 * @return Sensei_Course_Video_Blocks_Vimeo_Extension
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '4.6.4' );

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Sensei_Course_Video_Blocks_Vimeo_Extension constructor.
	 *
	 * @deprecated 4.6.4
	 */
	private function __construct() {
		_deprecated_function( __METHOD__, '4.6.4' );
	}


	/**
	 * Check if the URL is a Vimeo URL.
	 *
	 * @deprecated 4.6.4
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function is_supported( string $url ): bool {
		_deprecated_function( __METHOD__, '4.6.4' );

		$host = wp_parse_url( $url, PHP_URL_HOST );
		return in_array( $host, [ 'vimeo.com', 'player.vimeo.com' ], true );
	}

	/**
	 * Returns the class name for the extension.
	 *
	 * @deprecated 4.6.4
	 *
	 * @return string
	 */
	protected function get_extension_class_name(): string {
		_deprecated_function( __METHOD__, '4.6.4' );

		return 'vimeo-extension';
	}
}
