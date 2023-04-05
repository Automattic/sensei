<?php
/**
 * File containing Sensei_Bootstrap.
 *
 * @package Sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Bootstrap loads the functionality needed for Sensei_Main to initialize properly
 *
 * @deprecated $$next-version$$ Using Composer's autoloader now.
 *
 * @package Core
 */
class Sensei_Bootstrap {
	/**
	 * Singleton instance.
	 *
	 * @var Sensei_Bootstrap
	 */
	private static $instance;

	/**
	 * Set up Sensei class loaders and includes.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @return $this
	 */
	public function bootstrap() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		return $this;
	}

	/**
	 * Get singleton.
	 *
	 * @deprecated $$next-version$$
	 *
	 * @return Sensei_Bootstrap
	 */
	public static function get_instance() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
