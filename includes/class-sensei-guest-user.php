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
 * @since 4.11.0
 * @package Core
 */
class Sensei_Guest_User {

	/**
	 * Name of the Role for Guest Users.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	const ROLE = 'guest_student';

	/**
	 * Guest user login name prefix.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	const LOGIN_PREFIX = 'sensei_guest_';

	/**
	 * Guest user id.
	 *
	 * @since 4.11.0
	 *
	 * @var int
	 */
	private $guest_user_id = 0;

	/**
	 * Meta key for course open access setting.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	const COURSE_OPEN_ACCESS_META = '_open_access';

	/**
	 * List of actions to create a guest user for if the course is open access.
	 *
	 * @var array[] {
	 * @type string $field Form field.
	 * @type string $nonce Nonce field.
	 * @type bool   $enrol Whether to enrol the guest user before this action.
	 *                     }
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
	 * @since 4.11.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'log_guest_user_out_before_all_actions' ], 8 );

		add_action( 'wp', [ $this, 'init' ], 1 );

	}

	/**
	 * Initialize guest user feature.
	 *
	 * @since 4.11.0
	 */
	public function init() {
		/**
		 * Enable or disable 'open access course' feature.
		 *
		 * @hook  sensei_feature_open_access_courses
		 * @since 4.11.0
		 *
		 * @param {bool} $enable Enable feature. Default true.
		 *
		 * @return {bool} Wether to enable feature.
		 */
		if ( ! apply_filters( 'sensei_feature_open_access_courses', true ) ) {
			return;
		}

		add_action( 'wp', [ $this, 'log_in_guest_user_if_in_open_course' ], 8 );
		add_action( 'wp', [ $this, 'create_guest_user_and_login_for_open_course' ], 9 );
		add_action( 'sensei_is_enrolled', [ $this, 'open_course_always_enrolled' ], 10, 3 );
		add_action( 'sensei_can_access_course_content', [ $this, 'open_course_enable_course_access' ], 10, 2 );
		add_action( 'sensei_can_user_manually_enrol', [ $this, 'open_course_user_can_manualy_enroll' ], 10, 2 );
		add_action( 'sensei_send_emails', [ $this, 'skip_sensei_email' ] );

		$this->create_guest_student_role_if_not_exists();

	}

	/**
	 * Log out the guest user before any action, some actions like Log in Form does not work if guest user is logged in
	 * even after setting current user to 0 by 'wp' hook.
	 *
	 * @since 4.11.0
	 */
	public function log_guest_user_out_before_all_actions() {
		if (
			is_user_logged_in() &&
			$this->is_current_user_guest()
		) {
			$this->guest_user_id = get_current_user_id();
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Filter enrolment check to always return true if the course is open access.
	 *
	 * @since  4.11.0
	 *
	 * @param bool $is_enrolled Initial value.
	 * @param int  $user_id User ID. Unused.
	 * @param int  $course_id Course ID.
	 *
	 * @return bool
	 */
	public function open_course_always_enrolled( $is_enrolled, $user_id, $course_id ) {
		$in_course_content = is_singular( [ 'lesson', 'quiz' ] );
		return ( $in_course_content && $this->is_course_open_access( $course_id ) ) ? true : $is_enrolled;
	}

	/**
	 * Filter manual enrolment check to always allow users to manually enrol if the course is open access.
	 *
	 * @since  4.11.0
	 *
	 * @param bool $can_enroll Initial value.
	 * @param int  $course_id Course ID.
	 *
	 * @return bool
	 */
	public function open_course_user_can_manualy_enroll( $can_enroll, $course_id ) {

		$is_user_enrolled = is_user_logged_in() && Sensei_Course::is_user_enrolled( $course_id, get_current_user_id() );
		return $this->is_course_open_access( $course_id ) ? ! $is_user_enrolled : $can_enroll;
	}

	/**
	 * Filter course access check to always return true if the course is open access.
	 *
	 * @since  4.11.0
	 *
	 * @param bool $can_view_course_content Initial value.
	 * @param int  $course_id Course ID.
	 *
	 * @return bool
	 */
	public function open_course_enable_course_access( $can_view_course_content, $course_id ) {
		return $this->is_course_open_access( $course_id ) ? true : $can_view_course_content;
	}

	/**
	 * Create a guest user for open access courses if no user is logged in.
	 *
	 * @since 4.11.0
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
			$user_id = self::create_guest_user();
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
	 * @since 4.11.0
	 * @access private
	 */
	public function log_in_guest_user_if_in_open_course() {
		if (
			! is_user_logged_in() &&
			$this->is_open_course_related_action() &&
			$this->guest_user_id > 0
		) {
			wp_set_current_user( $this->guest_user_id );
		}
	}

	/**
	 * Checks if the action is related to an open course or a lesson or a quiz that belongs to an open course.
	 *
	 * @since 4.11.0
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
	 * @since  4.11.0
	 * @return boolean|mixed
	 */
	private function is_course_open_access( $course_id ) {
		$is_open_access = get_post_meta( $course_id, self::COURSE_OPEN_ACCESS_META, true );

		/**
		 * Filter if the given course has open access turned on.
		 *
		 * @hook  sensei_course_open_access
		 * @since 4.11.0
		 *
		 * @param {bool} $is_open_access Open access setting value.
		 * @param {int} $course_id Course ID.
		 *
		 * @return {bool} Open access setting value.
		 */
		return apply_filters( 'sensei_course_open_access', $is_open_access, $course_id );
	}

	/**
	 * Checks if the current user is a guest.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	private function is_current_user_guest() {
		$user = wp_get_current_user();
		return self::is_guest_user( $user );
	}

	/**
	 * Recreate nonce after logging in user invalidates existing one.
	 *
	 * @since 4.11.0
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
	 * @since  4.11.0
	 * @return int
	 */
	public static function create_guest_user() {
		$user_count = Sensei_Utils::get_user_count_for_role( self::ROLE ) + 1;
		$user_name  = self::LOGIN_PREFIX . wp_rand( 10000000, 99999999 ) . '_' . $user_count;
		return Sensei_Temporary_User::create_user(
			[
				'user_pass'    => wp_generate_password(),
				'user_login'   => $user_name,
				'user_email'   => $user_name . '@guest.senseilms',
				'display_name' => 'Guest Student ' . str_pad( $user_count, 3, '0', STR_PAD_LEFT ),
				'role'         => self::ROLE,
			]
		);
	}

	/**
	 * Delete a guest user and remove their course progress.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public static function delete_guest_user( $user_id ): void {

		if ( ! $user_id || ! self::is_guest_user( $user_id ) ) {
			return;
		}

		$course_ids = Sensei_Learner::instance()->get_enrolled_courses_query(
			$user_id,
			[
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		)->posts;

		foreach ( $course_ids as $course_id ) {
			Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );
		}

		Sensei_Temporary_User::delete_user( $user_id );
	}

	/**
	 * Log a user in.
	 *
	 * @param int $user_id ID of the user.
	 *
	 * @since 4.11.0
	 */
	private function login_user( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
	}

	/**
	 * Manually enrol the new user in the course.
	 *
	 * @since 4.11.0
	 *
	 * @param int $user_id User ID.
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
	 * @since 4.11.0
	 */
	private function create_guest_student_role_if_not_exists() {
		// Check if the Guest Student role exists.
		$guest_role = get_role( self::ROLE );

		// If Guest Student is not a valid WordPress role create it.
		if ( ! is_a( $guest_role, 'WP_Role' ) ) {
			// Create the role.
			add_role( self::ROLE, __( 'Guest Student', 'sensei-lms' ) );
		}
	}

	/**
	 * Determine if the current requests is for a supported action.
	 *
	 * @since 4.11.0
	 *
	 * @return string[]|null
	 */
	private function get_current_action() {

		/**
		 * Filters the list of supported actions for Guest Users.
		 *
		 * @hook  sensei_guest_user_supported_actions
		 * @since 4.11
		 *
		 * @param {array} List of supported actions for guest users.
		 *
		 * @return {array} List of supported actions for guest users.
		 */
		$supported_actions = apply_filters( 'sensei_guest_user_supported_actions', $this->supported_actions );

		foreach ( $supported_actions as $action ) {
			if ( $this->is_action( $action['field'], $action['nonce'] ) ) {
				return $action;
			}
		}

		return null;
	}

	/**
	 * Determines if the request is for an action submitting the given form field and nonce.
	 *
	 * @since  4.11.0
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

	/**
	 * Prevent Sensei emails related to guest user actions.
	 *
	 * @access private
	 * @since  4.11.0
	 *
	 * @param boolean $send_email Whether to send the email.
	 *
	 * @return boolean Whether to send the email.
	 */
	public function skip_sensei_email( $send_email ) {
		return $this->is_current_user_guest() ? false : $send_email;

	}

	/**
	 * Check if the given user is a guest user.
	 *
	 * @param WP_User|int $user User object or ID.
	 *
	 * @return bool
	 */
	private static function is_guest_user( $user ): bool {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}
		return in_array( self::ROLE, (array) $user->roles, true );
	}
}
