<?php
/**
 * File containing the Sensei_Home_Task_Publish_First_Course class.
 *
 * @package sensei-lms
 * @since 4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Task_Publish_First_Course class.
 *
 * @since 4.8.0
 */
class Sensei_Home_Task_Publish_First_Course implements Sensei_Home_Task {
	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'publish-first-course';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 600;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Publish your first course', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		$result = $this->load_result();
		if ( null === $result ) {
			// If we only have the demo course (or no course at all), let's point the user to the new course page.
			return admin_url( 'post-new.php?post_type=course' );
		}
		if ( '1' === $result->published ) {
			// We shouldn't return a URL if the task is completed.
			return null;
		}
		return get_edit_post_link( $result->ID, 'api' );
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		$result = $this->load_result();
		return null !== $result && '1' === $result->published;
	}

	/**
	 * Searches on the database for the latest course edited and/or published, so we can detect if
	 * the edit URL for that course and if the task was published with the same query.
	 *
	 * @return stdClass|null The row containing the result, or null if there's no course that isn't the sample course.
	 */
	protected function load_result() {
		global $wpdb;
		$cache_key   = 'home/tasks/publish-first-course';
		$cache_group = 'sensei/temporary';
		$result      = wp_cache_get( $cache_key, $cache_group );
		if ( false === $result ) {
			$prefix = $wpdb->esc_like( Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe-ish query.
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_status='publish' as published FROM {$wpdb->posts} WHERE post_type='course' AND post_status IN ('publish', 'draft') AND post_name NOT LIKE %s ORDER BY published DESC, ID ASC LIMIT 1", "{$prefix}%" ) );
			if ( null !== $result ) {
				wp_cache_set( $cache_key, $result, $cache_group, 60 );
			}
		}
		return $result;
	}

}
