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
		$cache_key   = 'home/tasks/create-first-course';
		$cache_group = 'sensei/temporary';
		$result      = wp_cache_get( $cache_key, $cache_group );
		if ( false === $result ) {
			$prefix = $wpdb->esc_like( Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe-ish and rare query.
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='course' AND post_status IN ('publish', 'draft') AND post_name NOT LIKE %s", "{$prefix}%" ) );
			if ( null === $result ) {
				$result = 0;
			} else {
				$result = (int) $result;
				wp_cache_set( $cache_key, $result, $cache_group, 60 );
			}
		}
		return $result > 0;
	}

}
