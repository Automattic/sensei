<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses_Interface interface.
 *
 * @package sensei
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview service interface.
 *
 * @since $$next-version$$
 */
interface Sensei_Reports_Overview_Service_Courses_Interface {

	/**
	 * Get total average progress value for courses.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Courses ids.
	 * @return float Total average progress value for all the courses.
	 */
	public function get_total_average_progress( array $course_ids ): float;

	/**
	 * Get the average grade of the courses.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return double Average grade of all courses.
	 */
	public function get_courses_average_grade( array $course_ids );

	/**
	 * Get average days to completion by courses.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return float Average days to completion, rounded to the highest integer.
	 */
	public function get_average_days_to_completion( array $course_ids ): float;

	/**
	 * Get total of enrollments.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return int Total of enrollments.
	 */
	public function get_total_enrollments( $course_ids ): int;
}
