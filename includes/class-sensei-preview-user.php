<?php
/**
 * Preview User
 *
 * Handles operations related to teachers switching to a preview user.
 *
 * @package Sensei\Frontend
 * @since   4.11.0
 */

/**
 * Sensei Preview User Class.
 *
 * @author  Automattic
 *
 * @since   4.11.0
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
	 * Preview user login name prefix.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	const LOGIN_PREFIX = 'sensei_preview_';

	/**
	 * Preview user class constructor.
	 *
	 * @since 4.11.0
	 */
	public function __construct() {

		add_action( 'wp', [ $this, 'init' ], 1 );

	}

	/**
	 * Initialize preview user feature.
	 *
	 * @since 4.11.0
	 */
	public function init() {

		/**
		 * Enable or disable 'preview as student' feature.
		 *
		 * @hook sensei_feature_preview_students
		 * @since 4.11.0
		 *
		 * @param {bool} $enable Enable feature. Default true.
		 *
		 * @return {bool} Wether to enable feature.
		 */
		if ( ! apply_filters( 'sensei_feature_preview_students', true ) ) {
			return;
		}

		add_action( 'wp', [ $this, 'switch_to_preview_user' ], 9 );
		add_action( 'wp', [ $this, 'switch_off_preview_user' ], 9 );
		add_action( 'wp', [ $this, 'override_user' ], 8 );
		add_action( 'wp', [ $this, 'add_preview_user_filters' ], 9 );
		add_action( 'show_admin_bar', [ $this, 'show_admin_bar_to_preview_user' ], 90 );
		add_action( 'admin_bar_menu', [ $this, 'add_user_switch_to_admin_bar' ], 90 );
		add_filter( 'sensei_is_enrolled', [ $this, 'preview_user_always_enrolled' ], 90, 3 );

		$this->create_role();
	}

	/**
	 * Activate filters used when a preview user is active.
	 *
	 * @access private
	 */
	public function add_preview_user_filters() {
		if ( $this->is_preview_user_active() ) {
			add_filter( 'map_meta_cap', [ $this, 'allow_post_preview' ], 10, 4 );
			add_filter( 'pre_get_posts', [ $this, 'count_unpublished_lessons' ], 10 );
			add_filter( 'sensei_notice', [ $this, 'hide_notices' ], 10, 1 );
			add_action( 'sensei_send_emails', '__return_false' );

		}

	}

	/**
	 * Change the current user to the preview user if its set for the teacher.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	public function override_user() {

		$course_id = Sensei_Utils::get_current_course();
		if ( ! $course_id ) {
			return;
		}

		$preview_user = $this->get_preview_user( get_current_user_id(), $course_id );

		if ( ! $preview_user ) {
			return;
		}

		// Clear out meta for the teacher if the preview user doesn't exist.
		if ( ! self::is_preview_user( $preview_user ) ) {
			self::delete_meta( get_current_user_id(), $preview_user, $course_id );
			return;
		}

		wp_set_current_user( $preview_user );
	}

	/**
	 * Create and switch to a preview user.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	public function switch_to_preview_user() {

		$course_id = Sensei_Utils::get_current_course();

		if ( ! $course_id || ! $this->is_action( self::SWITCH_ON_ACTION ) || ! $this->can_switch_to_preview_user( $course_id ) ) {
			return;
		}

		$preview_user_id = $this->create_preview_user( $course_id );
		$this->set_preview_user( $preview_user_id );

		wp_safe_redirect( remove_query_arg( self::SWITCH_ON_ACTION ) );

	}

	/**
	 * Switch back to original user and delete preview user.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	public function switch_off_preview_user() {

		if ( ! $this->is_action( self::SWITCH_OFF_ACTION ) ) {
			return;
		}

		self::delete_preview_user( get_current_user_id() );

		wp_safe_redirect( remove_query_arg( self::SWITCH_OFF_ACTION ) );

	}

	/**
	 * Add switch to user link to admin bar.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WordPress Admin Bar object.
	 */
	public function add_user_switch_to_admin_bar( $wp_admin_bar ) {

		$course_id = Sensei_Utils::get_current_course();
		if ( ! $course_id ) {
			return;
		}

		if ( $this->can_switch_to_preview_user( $course_id ) && ! $this->is_preview_user_active() ) {
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
		}

		if ( $this->is_preview_user_active() ) {
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
	 * @since 4.11.0
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
	 * Check if the current user can switch to a preview student for the course.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	private function can_switch_to_preview_user( $course_id ) {
		return Sensei_Course::can_current_user_edit_course( $course_id );
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
		$teacher      = wp_get_current_user();
		$user_name    = self::LOGIN_PREFIX . wp_rand( 10000000, 99999999 ) . '_' . $teacher->ID . '_' . $course_id;
		$display_name = 'Preview Student ' . $course_id . '-' . $teacher->ID . ' (' . $teacher->display_name . ')';

		return Sensei_Temporary_User::create_user(
			[
				'user_pass'    => wp_generate_password(),
				'user_login'   => $user_name,
				'user_email'   => $user_name . '@preview.senseilms',
				'display_name' => $display_name,
				'last_name'    => $display_name,
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
	public static function delete_preview_user( $user_id ) {

		if ( ! $user_id || ! self::is_preview_user( $user_id ) ) {
			return;
		}

		list( 'user' => $teacher, 'course' => $course_id ) = get_user_meta( $user_id, self::META, true );

		self::delete_meta( $teacher, $user_id, $course_id );

		Sensei_Temporary_User::delete_user( $user_id );
	}

	/**
	 * Create the Guest Student role if it does not exist.
	 *
	 * @since 4.11.0
	 */
	private function create_role() {
		$role = get_role( self::ROLE );

		if ( ! is_a( $role, 'WP_Role' ) ) {
			add_role( self::ROLE, __( 'Preview Student', 'sensei-lms' ) );
		}
	}

	/**
	 * Allow preview user to view draft posts.
	 *
	 * This effectively allows them the 'edit_post' and 'read_private_posts' caps, but this filter will only run on course frontend pages.
	 *
	 * @note This hook should only run when the preview user is active, it does not do checks on its own.
	 *
	 * @access private
	 *
	 * @param array  $caps    Capabilities.
	 * @param string $cap     Capability.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments.
	 *
	 * @return array
	 */
	public function allow_post_preview( $caps, $cap, $user_id, $args ) {

		if ( get_current_user_id() !== $user_id ) {
			return $caps;
		}

		if ( in_array( $cap, [ 'edit_post', 'read_private_posts' ], true ) ) {
			return [];
		}

		return $caps;
	}

	/**
	 * Hide draft course notices.
	 *
	 * @note This hook should only run when the preview user is active, it does not do checks on its own.
	 *
	 * @access private
	 *
	 * @param array $notice Notice.
	 *
	 * @return array|false
	 */
	public function hide_notices( $notice ) {

		if ( in_array( $notice['key'], [ 'sensei-course-outline-drafts' ], true ) ) {
			return false;
		}
		return $notice;
	}

	/**
	 * Change lesson queries to include unpublished lessons.
	 *
	 * Needed for course progress calculation (Sensei_Course::get_progress_stats).
	 *
	 * @note This hook should only run when the preview user is active, it does not do checks on its own.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param WP_Query $query Lesson query.
	 *
	 * @return void
	 */
	public function count_unpublished_lessons( WP_Query $query ) {
		if ( $query->get( 'post_type' ) === 'lesson' ) {
			$query->set( 'post_status', [ 'any' ] );
		}
	}

	/**
	 * Always treat preview user as enrolled in the course.
	 *
	 * @access private
	 *
	 * @param bool $is_enrolled Initial state.
	 * @param int  $user_id     User ID.
	 * @param int  $course_id   Course ID.
	 *
	 * @return bool
	 */
	public function preview_user_always_enrolled( $is_enrolled, $user_id, $course_id ) {

		if ( ! self::is_preview_user( $user_id ) ) {
			return $is_enrolled;
		}
		list( 'course' => $preview_course_id ) = get_user_meta( $user_id, self::META, true );
		if ( (int) $course_id === $preview_course_id ) {
			return true;
		}
		return $is_enrolled;
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
			self::delete_preview_user( $existing_preview_user );
		}

		$this->add_meta( $user_id, $preview_user_id, $course_id );
	}

	/**
	 * Check if the current user is a preview user.
	 *
	 * @return bool
	 */
	private function is_preview_user_active() {
		$user = wp_get_current_user();
		return self::is_preview_user( $user );
	}

	/**
	 * Set preview user meta for the teacher.
	 *
	 * @param int $teacher_user_id Teacher user ID.
	 * @param int $preview_user_id Preview user ID.
	 * @param int $course_id       Course ID.
	 *
	 * @return false|int
	 */
	private function add_meta( $teacher_user_id, $preview_user_id, $course_id ) {
		return add_user_meta(
			$teacher_user_id,
			self::META,
			self::meta_value( $preview_user_id, $course_id )
		);
	}

	/**
	 * Delete preview user meta for the teacher.
	 *
	 * @param int $teacher_user_id Teacher user ID.
	 * @param int $preview_user_id Preview user ID.
	 * @param int $course_id Course ID.
	 *
	 * @return void
	 */
	private static function delete_meta( $teacher_user_id, int $preview_user_id, $course_id ): void {
		delete_user_meta(
			$teacher_user_id,
			self::META,
			self::meta_value( $preview_user_id, $course_id )
		);

		Sensei_Utils::sensei_remove_user_from_course( $course_id, $preview_user_id );
	}

	/**
	 * Check if the given user is a preview user.
	 *
	 * @param WP_User|int $user User object or ID.
	 *
	 * @return bool
	 */
	private static function is_preview_user( $user ): bool {
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
