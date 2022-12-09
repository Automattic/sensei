<?php
/**
 * Guest User
 *
 * Handles operations related to allowing guest users take a course.
 *
 * @package Sensei\Frontend
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Guest User Class.
 *
 * @author Automattic
 *
 * @since $$next-version$$
 * @package Core
 */
class Sensei_Guest_User {

	/**
	 * Keeps a reference to the Guest Student role object
	 *
	 * @access protected
	 * @since $$next-version$$
	 *
	 * @var string
	 */
	protected $guest_student_role = 'guest_student';

	/**
	 * Sensei_Guest_User constructor.
	 *
	 * @since $$next-version$$
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'sensei_create_guest_user_and_login_for_open_course' ), 9 );

		$this->create_guest_student_role_if_not_exists();
	}

	/**
	 * Create a guest user for open access courses if no user is logged in.
	 *
	 * @since $$next-version$$
	 */
	public function sensei_create_guest_user_and_login_for_open_course() {
		global $post;

		// Conditionally create Guest Student user and set role for open course.
		if (
			$this->is_take_course_action()
			&& ! is_user_logged_in()
			&& $this->is_course_open_access( $post->ID )
		) {
			$user_id = $this->create_guest_student_user();
			$this->login_user( $user_id );
			$this->recreate_nonces();
		}
	}

	/**
	 * Check if the course is open access.
	 *
	 * @param  int $course_id ID of the course.
	 * @since  $$next-version$$
	 * @return boolean|mixed
	 */
	private function is_course_open_access( $course_id ) {
		return get_post_meta( $course_id, 'open_access', true );
	}

	/**
	 * Recreate nonce after logging in user invalidates existing one.
	 *
	 * @since $$next-version$$
	 */
	private function recreate_nonces() {
		$_POST['woothemes_sensei_start_course_noonce'] = wp_create_nonce( 'woothemes_sensei_start_course_noonce' );
	}

	/**
	 * Create a user with Guest Student role .
	 *
	 * @since  $$next-version$$
	 * @return int
	 */
	private function create_guest_student_user() {
		$user_count = get_user_count();
		$user_name  = 'guest_user_' . wp_rand( 10000000, 99999999 ) . '_' . $user_count;
		return wp_insert_user(
			[
				'user_pass'    => wp_generate_password(),
				'user_login'   => $user_name,
				'user_email'   => $user_name . '@senseiguest.senseiguest',
				'display_name' => 'Guest Student ' . $user_count,
				'role'         => $this->guest_student_role,
			]
		);
	}

	/**
	 * Log a user in.
	 *
	 * @param int $user_id ID of the user.
	 * @since $$next-version$$
	 */
	private function login_user( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
	}

	/**
	 * Determines if the request is for taking course and the course is not protected.
	 *
	 * @since  $$next-version$$
	 * @return boolean
	 */
	private function is_take_course_action() {
		global $post;

		return is_singular( 'course' )
			&& isset( $_POST['course_start'] )
			&& isset( $_POST['woothemes_sensei_start_course_noonce'] )
			&& wp_verify_nonce( wp_unslash( $_POST['woothemes_sensei_start_course_noonce'] ), 'woothemes_sensei_start_course_noonce' ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't modify the nonce.
			&& ! post_password_required( $post->ID );
	}

	/**
	 * Create the Guest Student role if it does not exist.
	 *
	 * @since $$next-version$$
	 */
	private function create_guest_student_role_if_not_exists() {
		// Check if the Guest Student role exists.
		$guest_role = get_role( $this->guest_student_role );

		// If Guest Student is not a valid WordPress role create it.
		if ( ! is_a( $guest_role, 'WP_Role' ) ) {
			// Create the role.
			add_role( $this->guest_student_role, __( 'Guest Student', 'sensei-lms' ) );
		}
	}
}
