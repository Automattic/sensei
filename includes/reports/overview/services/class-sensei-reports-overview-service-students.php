<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Students class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Progress_Query_Service_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Students overview service class.
 *
 * @since 4.4.1
 */
class Sensei_Reports_Overview_Service_Students {

	/**
	 * Get average grade of all lessons graded in all the courses filtered by students.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $user_ids user ids.
	 * @return double $graded_lesson_average_grade Average value of all the graded lessons in all the courses.
	 */
	public function get_graded_lessons_average_grade( $user_ids ) {
		if ( empty( $user_ids ) ) {
			return 0;
		}

		return ceil( ( new Progress_Query_Service_Factory() )->create_grading_stats_service()->get_users_average_grade( $user_ids ) );
	}
}
