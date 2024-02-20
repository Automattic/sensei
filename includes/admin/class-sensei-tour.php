<?php
/**
 * File containing the class Sensei_Tour.
 *
 * @package sensei-lms
 */

namespace Sensei\Admin\Tour;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles editor wizards.
 *
 * @since $$next-version$$
 */
class Sensei_Tour {

	/**
	 * Instance of class.
	 *
	 * @var self|null
	 */
	private static $instance;

	/**
	 * Sensei_Tour constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

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
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$post_type    = get_post_type();
		$tour_loaders = [];

		if ( in_array( $post_type, [ 'course', 'lesson' ], true ) ) {
			$tour_loaders[ "sensei-$post_type-tour" ] = [
				'path' => "admin/tour/$post_type-tour/index.js",
			];
		}

		/**
		 * Filters the tour loaders.
		 *
		 * @since $$next-version$$
		 *
		 * @param array $tour_loaders The tour loaders.
		 */
		$tour_loaders = apply_filters( 'sensei_tour_loaders', $tour_loaders );

		Sensei()->assets->enqueue( 'sensei-tour-styles', 'admin/tour/style.css', [] );

		foreach ( $tour_loaders as $handle => $tour_loader ) {
			Sensei()->assets->enqueue( $handle, $tour_loader['path'], [], true );
		}
	}
}
