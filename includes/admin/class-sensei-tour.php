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
 * Compatibility layer for the removed onboarding tours.
 *
 * @since 4.22.0
 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
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
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @return self
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '$$next-version$$' );

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the compatibility layer.
	 *
	 * @since 4.22.0
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 */
	public function init() {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Runs deprecated third-party tour loaders.
	 *
	 * @internal
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$post_type    = get_post_type();
		$tour_loaders = array();

		if (
			in_array( $post_type, array( 'course', 'lesson' ), true ) &&
			in_array( $hook, array( 'post-new.php', 'post.php' ), true )
		) {
			$handle                  = "sensei-$post_type-tour";
			$tour_loaders[ $handle ] = array(
				'minimum_install_version' => '4.22.0',
				'callback'                => $this->get_course_lesson_tour_enqueue_callback( $post_type, $handle ),
			);
		}

		/**
		 * Filters the tour loaders.
		 *
		 * @hook sensei_tour_loaders Load tours for Sensei.
		 *
		 * @since 4.22.0
		 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
		 *
		 * @param {array} $tour_loaders The tour loaders.
		 *
		 * @return {array} Filtered tour loaders.
		 */
		$tour_loaders = apply_filters_deprecated( 'sensei_tour_loaders', array( $tour_loaders ), '$$next-version$$' );

		$incomplete_tours = array();

		foreach ( $tour_loaders as $handle => $tour_loader ) {
			$install_version = \Sensei()->install_version ?? '';
			$install_version = $install_version ? $install_version : ''; // In case the value is false, null coalescing won't work.
			$minimum_version = $tour_loader['minimum_install_version'] ?? false;

			if ( $minimum_version && ! version_compare( $install_version, $minimum_version, '>=' ) ) {
				continue;
			}

			/**
			 * Filters the tour completion status.
			 *
			 * @hook sensei_tour_is_complete Check if a tour is complete.
			 *
			 * @since 4.22.0
			 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
			 *
			 * @param {bool}   $is_tour_complete The tour completion status.
			 * @param {string} $tour_id          The tour ID.
			 *
			 * @return {bool} Filtered tour completion status.
			 */
			$is_tour_complete = apply_filters_deprecated(
				'sensei_tour_is_complete',
				array( $this->get_tour_completion_status( $handle, get_current_user_id() ), $handle ),
				'$$next-version$$'
			);

			if ( ! $is_tour_complete ) {
				$incomplete_tours[ $handle ] = $tour_loader;
			}
		}

		foreach ( $incomplete_tours as $tour_loader ) {
			is_callable( $tour_loader['callback'] ) && call_user_func( $tour_loader['callback'], $hook );
		}
	}

	/**
	 * Set tour status for user.
	 *
	 * @since 4.22.0
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param string $tour_id The tour ID.
	 * @param bool   $status  The tour status.
	 * @param int    $user_id The user ID.
	 */
	public function set_tour_completion_status( $tour_id, $status, $user_id = 0 ) {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$tours = get_user_meta( $user_id, 'sensei_tours', true );

		if ( ! is_array( $tours ) ) {
				$tours = array();
		}

		$tours[ $tour_id ] = $status;
		update_user_meta( $user_id, 'sensei_tours', $tours );
	}

	/**
	 * Get tour status for user.
	 *
	 * @since 4.22.0
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param string $tour_id The tour ID.
	 * @param int    $user_id The user ID.
	 *
	 * @return bool The tour status.
	 */
	public function get_tour_completion_status( $tour_id, $user_id = 0 ) {
		_deprecated_function( __METHOD__, '$$next-version$$' );
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$tours = get_user_meta( $user_id, 'sensei_tours', true );

		if ( ! is_array( $tours ) ) {
			$tours = array();
		}

		return $tours[ $tour_id ] ?? false;
	}

	/**
	 * Get the former callback for a course or lesson tour.
	 *
	 * @since 4.23.0
	 * @deprecated $$next-version$$ The onboarding tours are no longer supported.
	 *
	 * @param string $post_type The post type.
	 * @param string $handle    The script handle.
	 *
	 * @return callable A no-op callback retained for backward compatibility.
	 */
	public function get_course_lesson_tour_enqueue_callback( $post_type, $handle ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Retained for backward compatibility.
		_deprecated_function( __METHOD__, '$$next-version$$' );

		return static function () {
		};
	}
}
