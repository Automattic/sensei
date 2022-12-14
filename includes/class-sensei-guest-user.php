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
	 * List of actions to create a guest user for if the course is open access.
	 *
	 * @var array[] {
	 *  @type string $field Form field.
	 *  @type string $nonce Nonce field.
	 *  @type bool $enrol Whether to enrol the guest user before this action.
	 * }
	 */
	protected $supported_actions = [
		// Take course.
		[
			'field' => 'course_start',
			'nonce' => 'woothemes_sensei_start_course_noonce',
			'enrol' => false,
		],
		// Lesson complete.
		[
			'field' => 'quiz_action',
			'nonce' => 'woothemes_sensei_complete_lesson_noonce',
			'enrol' => true,
		],
		// Quiz complete.
		[
			'field' => 'quiz_complete',
			'nonce' => 'woothemes_sensei_complete_quiz_nonce',
			'enrol' => true,
		],
		// Quiz save.
		[
			'field' => 'quiz_save',
			'nonce' => 'woothemes_sensei_save_quiz_nonce',
			'enrol' => true,
		],
		// Quiz pagination. (Saves answers on the page).
		[
			'field' => 'quiz_target_page',
			'nonce' => 'sensei_quiz_page_change_nonce',
			'enrol' => true,
		],
	];

	/**
	 * Sensei_Guest_User constructor.
	 *
	 * @since $$next-version$$
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'sensei_set_current_user_to_none_if_not_open_course_related_action' ], 8 );
		add_action( 'wp', [ $this, 'create_guest_user_and_login_for_open_course' ], 9 );
		add_action( 'sensei_is_enrolled', [ $this, 'open_course_always_enrolled' ], 10, 3 );
		add_action( 'sensei_can_access_course_content', [ $this, 'open_course_enable_course_access' ], 10, 2 );

		$this->create_guest_student_role_if_not_exists();
	}

	/**
	 * Filter enrolment check to always return true if the course is open access.
	 *
	 * @since  $$next-version$$
	 *
	 * @param bool $is_enrolled Initial value.
	 * @param int  $user_id     User ID. Unused.
	 * @param int  $course_id   Course ID.
	 *
	 * @return bool
	 */
	public function open_course_always_enrolled( $is_enrolled, $user_id, $course_id ) {
		$in_course_content = is_singular( [ 'lesson', 'quiz' ] );
		return ( $in_course_content && $this->is_course_open_access( $course_id ) ) ? true : $is_enrolled;
	}

	/**
	 * Filter course access check to always return true if the course is open access.
	 *
	 * @since  $$next-version$$
	 *
	 * @param bool $can_view_course_content Initial value.
	 * @param int  $course_id               Course ID.
	 *
	 * @return bool
	 */
	public function open_course_enable_course_access( $can_view_course_content, $course_id ) {
		return $this->is_course_open_access( $course_id ) ? true : $can_view_course_content;
	}

	/**
	 * Create a guest user for open access courses if no user is logged in.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	public function create_guest_user_and_login_for_open_course() {

		global $post;
		$course_id = Sensei_Utils::get_current_course();

		if ( empty( $course_id ) || is_user_logged_in() || ! $this->is_course_open_access( $course_id ) || post_password_required( $post->ID ) ) {
			return;
		}

		$current_action = $this->get_current_action();

		// Conditionally create Guest Student user and set role for open course.
		if ( $current_action ) {
			$user_id = $this->create_guest_user();
			$this->login_user( $user_id );
			$this->recreate_nonce( $current_action );

			if ( $current_action['enrol'] ) {
				$this->enrol_user( $user_id, $course_id );
			}
		}

	}

	/**
	 * Sets current guest user to none if out of open course context.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	public function sensei_set_current_user_to_none_if_not_open_course_related_action() {
		if (
			is_user_logged_in() &&
			$this->is_current_user_guest() &&
			! $this->is_open_course_related_action()
		) {
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Checks if the action is related to an open course or a lesson or a quiz that belongs to an open course.
	 *
	 * @since $$next-version$$
	 * @access private
	 * @return boolean
	 */
	private function is_open_course_related_action() {
		if ( ! is_singular( [ 'course', 'lesson', 'quiz' ] ) ) {
			return false;
		}

		return $this->is_course_open_access( Sensei_Utils::get_current_course() );
	}

	/**
	 * Check if the course is open access.
	 *
	 * @param int $course_id ID of the course.
	 *
	 * @since  $$next-version$$
	 * @return boolean|mixed
	 */
	private function is_course_open_access( $course_id ) {
		return get_post_meta( $course_id, 'open_access', true );
	}

	/**
	 * Checks if the current user is a guest.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	private function is_current_user_guest() {
		$user = wp_get_current_user();
		return in_array( $this->guest_student_role, (array) $user->roles, true );
	}
	/**
	 * Recreate nonce after logging in user invalidates existing one.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $action Action to recreate nonce for.
	 */
	private function recreate_nonce( $action ) {
		$nonce           = $action['nonce'];
		$_POST[ $nonce ] = wp_create_nonce( $nonce );
	}

	/**
	 * Create a user with Guest Student role .
	 *
	 * @since  $$next-version$$
	 * @return int
	 */
	private function create_guest_user() {
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
	 *
	 * @since $$next-version$$
	 */
	private function login_user( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
	}

	/**
	 * Manually enrol the new user in the course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	private function enrol_user( $user_id, $course_id ) {
		if ( ! Sensei_Course::can_current_user_manually_enrol( $course_id )
			|| ! Sensei_Course::is_prerequisite_complete( $course_id ) ) {
			return; // Error message?
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$course_enrolment->enrol( $user_id );
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

	/**
	 * Determine if the current requests is for a supported action.
	 *
	 * @since $$next-version$$
	 *
	 * @return string[]|null
	 */
	private function get_current_action() {

		foreach ( $this->supported_actions as $action ) {
			if ( $this->is_action( $action['field'], $action['nonce'] ) ) {
				return $action;
			}
		}

		return null;
	}

	/**
	 * Determines if the request is for an action submitting the given form field and nonce.
	 *
	 * @since  $$next-version$$
	 *
	 * @param string $field Form field name for the action.
	 * @param string $nonce Nonce name for the action.
	 *
	 * @return boolean
	 */
	private function is_action( $field, $nonce ) {
		return isset( $_POST[ $field ] )
			&& isset( $_POST[ $nonce ] )
			&& wp_verify_nonce( wp_unslash( $_POST[ $nonce ] ), $nonce ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification
	}
}
