<?php
/**
 * File containing the Sensei_Home_Task_Create_First_Course class.
 *
 * @package sensei-lms
 * @since 4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Task_Create_First_Course class.
 *
 * @since 4.8.0
 */
class Sensei_Home_Task_Create_First_Course implements Sensei_Home_Task {
	const CREATED_FIRST_COURSE_OPTION_KEY = 'sensei_home_task_created_first_course';

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'create-first-course';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 200;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Create your first course', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return admin_url( 'post-new.php?post_type=course' );
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		global $wpdb;

		$task_completed = get_option( self::CREATED_FIRST_COURSE_OPTION_KEY, -1 );

		// Option does not exist.
		if ( -1 === $task_completed ) {
			$prefix = $wpdb->esc_like( Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe-ish and should only run once.
			$result         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='course' AND post_status IN ('publish', 'draft') AND post_name NOT LIKE %s", "{$prefix}%" ) );
			$task_completed = ( $result > 0 ) ? 1 : 0;

			update_option( self::CREATED_FIRST_COURSE_OPTION_KEY, $task_completed, false );
		}

		return (bool) $task_completed;
	}
}
