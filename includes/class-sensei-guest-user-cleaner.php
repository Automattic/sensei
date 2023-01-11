<?php
/**
 * Guest User Cleaner
 *
 * Handles cleaning of guest users who are not active.
 *
 * @package sensei-lms
 *
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Guest User Cleaner Class.
 *
 * @author Automattic
 *
 * @since $$next-version$$
 * @package Core
 */
class Sensei_Guest_User_Cleaner {

	/**
	 * Instance of singleton.
	 *
	 * @since $$next-version$$
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches an instance of the class.
	 *
	 * @since $$next-version$$
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
	 * Sensei_Guest_User_Cleaner constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Sensei_Guest_User_Cleaner constructor.
	 *
	 * @since $$next-version$$
	 */
	public function init() {
		add_action( 'init', [ $this, 'maybe_schedule_cron_jobs' ], 101 );
		add_action( 'sensei_remove_inactive_guest_users', [ $this, 'clean_inactive_guest_users' ] );
	}


	/**
	 * Attaches guest user cleaning job to cron.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	public function maybe_schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'sensei_remove_inactive_guest_users' ) ) {
			wp_schedule_event( time(), 'daily', 'sensei_remove_inactive_guest_users' );
		}
	}

	/**
	 * Remove guest users who have not been active within last week.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	public function clean_inactive_guest_users() {
		$user_ids_to_be_deleted = $this->get_inactive_users();

		foreach ( $user_ids_to_be_deleted as $user_id ) {
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
	}

	/**
	 * Get a list of guest users who have not been active within last week.
	 *
	 * @access private
	 *
	 * @return array List of user IDs.
	 */
	private function get_inactive_users() {
		$guest_user_ids = get_users(
			[
				'fields' => 'ID',
				'role'   => 'guest_student',
			]
		);

		$activity_args = [
			'user_id'    => $guest_user_ids,
			'type_in'    => [ 'sensei_lesson_status', 'sensei_course_status' ],
			'status'     => 'any',
			'date_query' => [
				[
					'after' => '1 week ago',
				],
			],
		];

		$last_week_activities = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( $last_week_activities && is_a( $last_week_activities, 'WP_Comment' ) ) {
			$last_week_activities = [ $last_week_activities ];
		} elseif ( ! is_array( $last_week_activities ) ) {
			$last_week_activities = [];
		}

		return array_values( array_diff( $guest_user_ids, array_column( $last_week_activities, 'user_id' ) ) );
	}
}
