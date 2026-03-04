<?php
/**
 * File containing the Progress_Query_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Progress_Query_Service_Interface.
 *
 * Provides aggregate query methods for student progress data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Progress_Query_Service_Interface {

	/**
	 * Get the sum of all user grades for the given course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	public function get_course_users_grades_sum( int $course_id ): int;
}
