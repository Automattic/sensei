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
 * @since 4.22.0
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
	 * @since 4.22.0
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
	 *
	 * @since 4.22.0
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @internal
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$post_type    = get_post_type();
		$tour_loaders = [];

		if (
			in_array( $post_type, [ 'course', 'lesson' ], true ) &&
			in_array( $hook, [ 'post-new.php', 'post.php' ], true )
		) {
			$handle                  = "sensei-$post_type-tour";
			$tour_loaders[ $handle ] = [
				'minimum_install_version' => '4.22.0',
				'callback'                => $this->get_course_lesson_tour_enqueue_callback( $post_type, $handle ),
			];
		}

		/**
		 * Filters the tour loaders.
		 *
		 * @hook sensei_tour_loaders Load tours for Sensei.
		 *
		 * @since 4.22.0
		 *
		 * @param {array} $tour_loaders The tour loaders.
		 *
		 * @return {array} Filtered tour loaders.
		 */
		$tour_loaders = apply_filters( 'sensei_tour_loaders', $tour_loaders );

		$incomplete_tours = [];

		foreach ( $tour_loaders as $handle => $tour_loader ) {
			$install_version = \Sensei()->install_version ?? '';
			$install_version = $install_version ? $install_version : ''; // In case the value is false, null coalescing won't work.
			$minimum_version = $tour_loader['minimum_install_version'] ?? false;

			if (
				$minimum_version &&
				! version_compare( $install_version, $tour_loader['minimum_install_version'] ?? '', '>=' )
			) {
				continue;
			}

			/**
			 * Filters the tour completion status.
			 *
			 * @hook sensei_tour_is_complete Check if a tour is complete.
			 *
			 * @since 4.22.0
			 *
			 * @param {bool}  $is_tour_complete The tour completion status.
			 * @param {string} $tour_id The tour ID.
			 *
			 * @return {bool} Filtered tour completion status.
			 */
			$is_tour_complete = apply_filters( 'sensei_tour_is_complete', $this->get_tour_completion_status( $handle, get_current_user_id() ), $handle );
			if ( ! $is_tour_complete ) {
				$incomplete_tours[ $handle ] = $tour_loader;
			}
		}

		if ( ! empty( $incomplete_tours ) ) {
			Sensei()->assets->enqueue( 'sensei-tour-styles', 'admin/tour/style.css', [] );
		}

		foreach ( $incomplete_tours as $handle => $tour_loader ) {
			is_callable( $tour_loader['callback'] ) && call_user_func( $tour_loader['callback'], $hook );
		}
	}

	/**
	 * Set tour status for user.
	 *
	 * @since 4.22.0
	 *
	 * @param string $tour_id The tour ID.
	 * @param bool   $status  The tour status.
	 * @param int    $user_id The user ID.
	 */
	public function set_tour_completion_status( $tour_id, $status, $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$tours = get_user_meta( $user_id, 'sensei_tours', true );

		if ( ! is_array( $tours ) ) {
			$tours = [];
		}

		$tours[ $tour_id ] = $status;

		update_user_meta( $user_id, 'sensei_tours', $tours );
	}

	/**
	 * Get tour status for user.
	 *
	 * @since 4.22.0
	 *
	 * @param string $tour_id The tour ID.
	 * @param int    $user_id The user ID.
	 *
	 * @return bool The tour status.
	 */
	public function get_tour_completion_status( $tour_id, $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$tours = get_user_meta( $user_id, 'sensei_tours', true );

		if ( ! is_array( $tours ) ) {
			$tours = [];
		}

		return $tours[ $tour_id ] ?? false;
	}

	/**
	 * Get the callback to enqueue the course or lesson tour.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $post_type The post type.
	 * @param string $handle    The script handle.
	 *
	 * @return callable The callback to enqueue the course or lesson tour.
	 */
	public function get_course_lesson_tour_enqueue_callback( $post_type, $handle ) {
		return function () use ( $post_type, $handle ) {
			Sensei()->assets->enqueue( $handle, "admin/tour/$post_type-tour/index.js", [], true );
		};
	}
}
