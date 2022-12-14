<?php
/**
 * Preview User
 *
 * Handles operations related to teachers switching to a preview user.
 *
 * @package Sensei\Frontend
 * @since   $$next-version$$
 */

/**
 * Sensei Preview User Class.
 *
 * @author  Automattic
 *
 * @since   $$next-version$$
 * @package Core
 */
class Sensei_Preview_User {

	/**
	 * Preview user role.
	 */
	const ROLE = 'preview_student';

	/**
	 * Switch to/from preview user actions.
	 */
	const SWITCH_ON_ACTION  = 'sensei-preview-as-student';
	const SWITCH_OFF_ACTION = 'sensei-exit-student-preview';

	/**
	 * Meta key for the associated preview user ID.
	 * Used to link the original teacher and the preview user, in both directions.
	 */
	const META = 'sensei_previewing_user';

	/**
	 * Set up preview user hooks.
	 *
	 * @since $$next-version$$
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'switch_to_preview_user' ], 9 );
		add_action( 'wp', [ $this, 'switch_off_preview_user' ], 9 );
		add_action( 'wp', [ $this, 'override_user' ], 8 );
		add_action( 'show_admin_bar', [ $this, 'show_admin_bar_to_preview_user' ], 90 );
		add_action( 'admin_bar_menu', [ $this, 'add_user_switch_to_admin_bar' ], 90 );

		$this->create_role();
	}

	/**
	 * Change the current user to the preview user if its set for the teacher.
	 *
	 * @since  $$next-version$$
	 * @access private
	 */
	public function override_user() {

		$course_id = Sensei_Utils::get_current_course();
		if ( ! $course_id ) {
			return;
		}

		$preview_user = $this->get_preview_user( get_current_user_id(), $course_id );

		if ( ! $preview_user || ! $this->is_preview_user( $preview_user ) ) {
			return;
		}

		wp_set_current_user( $preview_user );
	}

	/**
	 * Create and switch to a preview user.
	 *
	 * @since  $$next-version$$
	 * @access private
	 */
	public function switch_to_preview_user() {

		$course_id = Sensei_Utils::get_current_course();

		if ( ! $course_id || ! $this->is_action( self::SWITCH_ON_ACTION ) ) {
			return;
		}

		$preview_user_id = $this->create_preview_user( $course_id );
		$this->set_preview_user( $preview_user_id );

		wp_safe_redirect( remove_query_arg( self::SWITCH_ON_ACTION ) );

	}

	/**
	 * Switch back to original user and delete preview user.
	 *
	 * @since  $$next-version$$
	 * @access private
	 */
	public function switch_off_preview_user() {

		if ( ! $this->is_action( self::SWITCH_OFF_ACTION ) ) {
			return;
		}

		$this->delete_preview_user( get_current_user_id() );

		wp_safe_redirect( remove_query_arg( self::SWITCH_OFF_ACTION ) );

	}

	/**
	 * Add switch to user link to admin bar.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WordPress Admin Bar object.
	 */
	public function add_user_switch_to_admin_bar( $wp_admin_bar ) {

		if ( ! Sensei_Utils::get_current_course() ) {
			return;
		}

		if ( ! $this->is_preview_user_active() ) {
			$wp_admin_bar->add_node(
				[
					'id'     => self::SWITCH_ON_ACTION,
					'title'  => __( 'Preview as Student', 'sensei-lms' ),
					'parent' => 'top-secondary',
					'href'   => add_query_arg( [ self::SWITCH_ON_ACTION => wp_create_nonce( self::SWITCH_ON_ACTION ) ] ),
					'meta'   => [
						'class' => 'sensei-user-switch-preview',
					],
				]
			);
		} else {
			$wp_admin_bar->add_node(
				[
					'id'     => self::SWITCH_OFF_ACTION,
					'title'  => __( 'Exit Student Preview', 'sensei-lms' ),
					'parent' => 'top-secondary',
					'href'   => add_query_arg( [ self::SWITCH_OFF_ACTION => wp_create_nonce( self::SWITCH_OFF_ACTION ) ] ),
					'meta'   => [
						'class' => 'sensei-user-switch-preview',
					],
				]
			);
		}

	}

	/**
	 * Enable admin bar for preview user.
	 *
	 * @since  $$next-version$$
	 * @access private
	 *
	 * @param bool $show Initial state.
	 *
	 * @return bool
	 */
	public function show_admin_bar_to_preview_user( $show ) {
		if ( $this->is_preview_user_active() ) {
			return true;
		}

		return $show;
	}

	/**
	 * Check if the request is for the given action.
	 *
	 * @param string $action Action field and nonce name.
	 *
	 * @return bool
	 */
	private function is_action( $action ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification
		return isset( $_GET[ $action ] ) && wp_verify_nonce( wp_unslash( $_GET[ $action ] ), $action );
	}


	/**
	 * Create a preview user for the current teacher.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return int
	 */
	private function create_preview_user( $course_id ) {
		$teacher    = wp_get_current_user();
		$user_count = get_user_count();
		$user_name  = 'preview_user_' . wp_rand( 10000000, 99999999 ) . '_' . $user_count;

		return wp_insert_user(
			[
				'user_pass'    => wp_generate_password(),
				'user_login'   => $user_name,
				'user_email'   => $user_name . '@senseipreview.senseipreview',
				'display_name' => 'Preview Student ' . $user_count . ' (' . $teacher->display_name . ')',
				'role'         => self::ROLE,
				'meta_input'   => [
					self::META => self::meta_value( $teacher->ID, $course_id ),
				],
			]
		);
	}

	/**
	 * Delete preview user, including their course progress data.
	 *
	 * @param int $user_id User ID for the preview user.
	 */
	private function delete_preview_user( $user_id ) {

		if ( ! $user_id || ! $this->is_preview_user( $user_id ) ) {
			return;
		}

		list( 'user' => $teacher, 'course' => $course_id ) = get_user_meta( $user_id, self::META, true );

		delete_user_meta(
			$teacher,
			self::META,
			self::meta_value( $user_id, $course_id )
		);

		Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		wp_delete_user( $user_id );
	}

	/**
	 * Create the Guest Student role if it does not exist.
	 *
	 * @since $$next-version$$
	 */
	private function create_role() {
		$role = get_role( self::ROLE );

		if ( ! is_a( $role, 'WP_Role' ) ) {
			add_role( self::ROLE, __( 'Preview Student', 'sensei-lms' ) );
		}
	}

	/**
	 * Get preview user for the teacher and course if one is active.
	 *
	 * @param int $user_id   Teacher user ID.
	 * @param int $course_id Course ID.
	 *
	 * @return false|int
	 */
	private function get_preview_user( $user_id, $course_id ) {
		$preview_users = get_user_meta( $user_id, self::META, false );
		if ( empty( $preview_users ) || ! $course_id ) {
			return false;
		}
		foreach ( $preview_users as $preview_user ) {
			if ( $preview_user['course'] === $course_id ) {
				return $preview_user['user'];
			}
		}
		return false;
	}

	/**
	 * Store preview user for the current teacher as user meta.
	 *
	 * @param int $preview_user_id Preview user ID.
	 */
	private function set_preview_user( $preview_user_id ) {
		list( 'course' => $course_id ) = get_user_meta( $preview_user_id, self::META, true );
		$user_id                       = get_current_user_id();
		$existing_preview_user         = $this->get_preview_user( $user_id, $course_id );

		if ( $existing_preview_user ) {
			$this->delete_preview_user( $existing_preview_user );
		}

		add_user_meta(
			get_current_user_id(),
			self::META,
			self::meta_value( $preview_user_id, $course_id )
		);
	}

	/**
	 * Check if the current user is a preview user.
	 *
	 * @return bool
	 */
	private function is_preview_user_active() {
		$user = wp_get_current_user();
		return $this->is_preview_user( $user );
	}

	/**
	 * Check if the given user is a preview user.
	 *
	 * @param WP_User|int $user User object or ID.
	 *
	 * @return bool
	 */
	private function is_preview_user( $user ): bool {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}
		if ( ! is_a( $user, 'WP_User' ) ) {
			return false;
		}
		return in_array( self::ROLE, (array) $user->roles, true );
	}

	/**
	 * Format meta value.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return array
	 */
	private static function meta_value( $user_id, $course_id ) {
		return [
			'user'   => absint( $user_id ),
			'course' => absint( $course_id ),
		];
	}

}
