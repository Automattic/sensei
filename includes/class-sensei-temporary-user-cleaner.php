<?php
/**
 * Temporary User Cleaner
 *
 * Handles cleaning of guest and preview users who are not active.
 *
 * @package sensei-lms
 *
 * @since 4.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Temporary User Cleaner Class.
 *
 * @author Automattic
 *
 * @since 4.11.0
 * @package Core
 */
class Sensei_Temporary_User_Cleaner {

	/**
	 * Instance of singleton.
	 *
	 * @since 4.11.0
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches an instance of the class.
	 *
	 * @since 4.11.0
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
	 * Sensei_Temporary_User_Cleaner constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Add hooks to schedule cleaning job..
	 *
	 * @since 4.11.0
	 */
	public function init() {
		add_action( 'init', [ $this, 'maybe_schedule_cron_jobs' ], 101 );
		add_action( 'sensei_remove_inactive_guest_users', [ $this, 'clean_inactive_guest_users' ] );
	}


	/**
	 * Attach cleaning job to cron.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	public function maybe_schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'sensei_remove_inactive_guest_users' ) ) {
			wp_schedule_event( time(), 'daily', 'sensei_remove_inactive_guest_users' );
		}
	}

	/**
	 * Remove guest and preview users who have not been active within last week.
	 *
	 * @since 4.11.0
	 * @access private
	 */
	public function clean_inactive_guest_users() {
		$user_ids_to_be_deleted = $this->get_inactive_users();

		foreach ( $user_ids_to_be_deleted as $user_id ) {
			Sensei_Guest_User::delete_guest_user( $user_id );
			Sensei_Preview_User::delete_preview_user( $user_id );
		}
	}

	/**
	 * Get a list of temporary users who have not been active within last week.
	 *
	 * @access private
	 *
	 * @return array List of user IDs.
	 */
	private function get_inactive_users() {

		$guest_user_ids = Sensei_Temporary_User::get_all_users(
			[
				'fields'   => 'ID',
				'role__in' => [ Sensei_Guest_User::ROLE, Sensei_Preview_User::ROLE ],
			]
		);

		if ( empty( $guest_user_ids ) ) {
			return [];
		}

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

		remove_filter( 'sensei_check_for_activity', [ Sensei_Temporary_User::class, 'filter_sensei_activity' ], 10, 2 );
		$last_week_activities = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( $last_week_activities && is_a( $last_week_activities, 'WP_Comment' ) ) {
			$last_week_activities = [ $last_week_activities ];
		} elseif ( ! is_array( $last_week_activities ) ) {
			$last_week_activities = [];
		}

		return array_values( array_diff( $guest_user_ids, array_column( $last_week_activities, 'user_id' ) ) );
	}

}
