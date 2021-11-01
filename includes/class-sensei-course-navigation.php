<?php
/**
 * File containing Sensei_Course_Navigation class.
 *
 * @package sensei-lms
 * @since 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Navigation class.
 *
 * @since 3.16.0
 */
class Sensei_Course_Navigation {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Navigation constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the Course Navigation.
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {
		if ( ! $sensei->feature_flags->is_enabled( 'course_navigation' ) ) {
			// As soon this feature flag check is removed, the `$sensei` argument can also be removed.
			return;
		}
	}
}
