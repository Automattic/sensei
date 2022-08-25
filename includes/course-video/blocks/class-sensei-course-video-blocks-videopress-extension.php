<?php
/**
 * File containing the Sensei_Course_Video_Blocks_VideoPress_Extension class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extends standard Embed block with VideoPress specific functionality for video course progression
 *
 * @since 3.15.0
 *
 * @deprecated $$next-version$$
 */
class Sensei_Course_Video_Blocks_VideoPress_Extension extends Sensei_Course_Video_Blocks_Embed_Extension {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Returns an instance of the class.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @return static
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Sensei_Course_Video_Blocks_VideoPress_Extension constructor.
	 *
	 * @deprecated $$next-version$$
	 */
	private function __construct() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
	}

	/**
	 * Check if the URL is a VideoPress URL.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function is_supported( string $url ): bool {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		$host = wp_parse_url( $url, PHP_URL_HOST );

		return strpos( $host, 'videopress.com' ) !== false || strpos( $host, 'video.wordpress.com' ) !== false;
	}

	/**
	 * Returns the class name for the extension.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @return string
	 */
	protected function get_extension_class_name(): string {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		return 'videopress-extension';
	}
}
