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
	 * Name of the Role for Guest Users.
	 *
	 * @since $$next-version$$
	 *
	 * @var string
	 */
	const ROLE = 'guest_student';

	/**
	 * Guest user login name prefix.
	 *
	 * @since $$next-version$$
	 *
	 * @var string
	 */
	const LOGIN_PREFIX = 'sensei_guest_';

	/**
	 * Guest user id.
	 *
	 * @since $$next-version$$
	 *
	 * @var int
	 */
	private $guest_user_id = 0;

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
		add_action( 'init', [ $this, 'log_guest_user_out_before_all_actions' ], 8 );
		add_action( 'wp', [ $this, 'sensei_log_existing_guest_user_in_if_open_course_related_action' ], 8 );
		add_action( 'wp', [ $this, 'create_guest_user_and_login_for_open_course' ], 9 );
		add_action( 'sensei_is_enrolled', [ $this, 'open_course_always_enrolled' ], 10, 3 );
		add_action( 'sensei_can_access_course_content', [ $this, 'open_course_enable_course_access' ], 10, 2 );
		add_action( 'sensei_send_emails', [ $this, 'skip_sensei_email' ] );

		$this->create_guest_student_role_if_not_exists();
	}

	/**
	 * Log out the guest user before any action, some actions like Log in Form does not work if guest user is logged in
	 * even after setting current user to 0 by 'wp' hook.
	 *
	 * @since $$next-version$$
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
	public function sensei_log_existing_guest_user_in_if_open_course_related_action() {
		if (
			! is_user_logged_in() &&
			$this->is_open_course_related_action() &&
			$this->guest_user_id > 0
		) {
			wp_set_current_user( $this->guest_user_id );
		}
	}

	/**
	 * Initializes the backend and admin actions for Guest users.
	 *
	 * @since $$next-version$$
	 */
	public static function init_guest_user_admin() {
		add_filter( 'editable_roles', [ static::class, 'filter_out_guest_student_role' ], 11 );
		add_filter( 'views_users', [ static::class, 'filter_out_guest_user_tab_from_users_list' ] );

		add_action( 'pre_user_query', [ static::class, 'filter_out_guest_users' ], 11 );
	}

	/**
	 * Filter out Guest Student role tab from Users page in Settings.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 *  @param array $views List of tabs.
	 */
	public static function filter_out_guest_user_tab_from_users_list( $views ) {
		unset( $views[ self::ROLE ] );
		return $views;
	}

	/**
	 * Remove Guest Student role from showing up Settings.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 *  @param array $roles List of roles.
	 */
	public static function filter_out_guest_student_role( $roles ) {
		unset( $roles[ self::ROLE ] );
		return $roles;
	}

	/**
	 * Remove guest users from user queries.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 *  @param WP_User_Query $query The user query.
	 */
	public static function filter_out_guest_users( WP_User_Query $query ) {
		global $wpdb;

		$query->query_where = str_replace(
			'WHERE 1=1',
			"WHERE 1=1 AND {$wpdb->users}.user_login NOT LIKE '" . self::LOGIN_PREFIX . "%'",
			$query->query_where
		);
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
		return in_array( self::ROLE, (array) $user->roles, true );
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
		$user_count = Sensei_Utils::get_user_count_for_role( self::ROLE ) + 1;
		$user_name  = self::LOGIN_PREFIX . wp_rand( 10000000, 99999999 ) . '_' . $user_count;
		return Sensei_Temporary_User::create_user(
			[
				'user_pass'    => wp_generate_password(),
				'user_login'   => $user_name,
				'user_email'   => $user_name . '@senseiguest.senseiguest',
				'display_name' => 'Guest Student ' . str_pad( $user_count, 3, '0', STR_PAD_LEFT ),
				'role'         => self::ROLE,
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

	/**
	 * Prevent Sensei emails related to guest user actions.
	 *
	 * @access private
	 * @since  $$next-version$$
	 *
	 * @param boolean $send_email Whether to send the email.
	 *
	 * @return boolean Whether to send the email.
	 */
	public function skip_sensei_email( $send_email ) {
		return $this->is_current_user_guest() ? false : $send_email;

	}
}
