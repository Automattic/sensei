<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview service class.
 *
 * @since 4.5.0
 */
class Sensei_Reports_Overview_Service_Courses {

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Get total average progress value for courses.
	 *
	 * @since  4.5.0
	 * @access public
	 *
	 * @return float total average progress value for all the courses.
	 */
	public function get_total_average_progress(): float {
		$courses_ids = $this->get_all_courses_ids();
		if ( empty( $courses_ids ) ) {
			return false;
		}
		$lessons_count_per_courses = $this->get_lessons_in_courses( $courses_ids );
		$lessons_completions       = $this->get_lessons_completions();
		$student_count_per_courses = $this->get_students_count_in_courses( $courses_ids );
		$total_average_progress    = 0;

		foreach ( $courses_ids as $course_id ) {
			if ( ! isset( $lessons_count_per_courses[ $course_id ] ) || ! isset( $student_count_per_courses[ $course_id ] ) ) {
				continue;
			}
			// Get lessons in the course.
			$lessons = $lessons_count_per_courses[ $course_id ]->lessons;
			$lessons = array_map( 'intval', explode( ',', $lessons ) );
			if ( empty( $lessons ) ) {
				continue;
			}

			// Get students count.
			$students_count = $student_count_per_courses[ $course_id ]->students_count;
			if ( ! $students_count ) {
				continue;
			}

			// Get all completed lessons for all the students.
			$completed_count = array_reduce(
				$lessons,
				function ( $carry, $lesson ) use ( $lessons_completions ) {
					if ( ! isset( $lessons_completions[ $lesson ] ) ) {
						return $carry;
					}
					$carry += $lessons_completions[ $lesson ]->completion_count;
					return $carry;
				},
				0
			);

			// Calculate average progress for a course.
			$course_average_progress = $completed_count / ( $students_count * count( $lessons ) ) * 100;

			// Add value to the total average progress.
			$total_average_progress += $course_average_progress;
		}
		// Divide total value to get average total value for average progress for courses.
		$average_total_average_progress = ceil( $total_average_progress / count( $courses_ids ) );
		return $average_total_average_progress;
	}


	/**
	 * Get all lessons completions.
	 *
	 * @since  4.5.0
	 *
	 * @return array lessons completions.
	 */
	private function get_lessons_completions(): array {

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			"SELECT wcom.comment_post_id lesson_id, COUNT(*) completion_count
						FROM {$wpdb->comments} wcom
						WHERE wcom.comment_approved IN ('graded', 'ungraded', 'passed', 'failed','complete')
						AND comment_type IN ('sensei_lesson_status')
						AND wcom.comment_post_ID IN
						(
						SELECT wpm.post_id lesson_id from {$wpdb->posts} wpc
						JOIN {$wpdb->postmeta} wpm on wpm.meta_value = wpc.id
						WHERE wpm.meta_key = '_lesson_course'
						AND wpc.post_status in ('publish','private')
						)
						GROUP BY wcom.comment_post_id",
			'OBJECT_K'
		);
	}

	/**
	 * Get all courses ids no pagination.
	 *
	 * @since  4.5.0
	 *
	 * @return array course ids array.
	 */
	private function get_all_courses_ids(): array {
		// Get all courses ids.
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ID',
		);

		$courses_query = new WP_Query( $args );

		return array_column( $courses_query->posts, 'ID' );
	}

	/**
	 * Get lessons grouped by courses.
	 *
	 * @since  4.5.0
	 *
	 * @param array $courses_ids The list of courses ids.
	 * @return array lessons count in courses.
	 */
	private function get_lessons_in_courses( $courses_ids ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
		// phpcs:ignore
			$wpdb->prepare(
				"SELECT pm.meta_value as course_id, GROUP_CONCAT(pm.post_id) as lessons
				FROM {$wpdb->postmeta} pm
				WHERE pm.meta_value IN (%1s)" // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- no need for quoting.
				. " AND pm.meta_key = '_lesson_course'
				GROUP BY pm.meta_value",
				implode( ',', $courses_ids )
			),
			'OBJECT_K'
		);
	}

	/**
	 * Get students count by courses.
	 *
	 * @since  4.5.0
	 *
	 * @param array $courses_ids The array of courses ids.
	 * @return array students in courses.
	 */
	private function get_students_count_in_courses( array $courses_ids ): array {

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe direct sql.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.comment_post_ID as course_id, count(c.comment_post_ID) as students_count
					FROM {$wpdb->comments} c
					WHERE c.comment_post_ID IN (%1s)" // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- no quoting.
				. " AND c.comment_type = 'sensei_course_status'
					AND c.comment_approved IN ( 'in-progress', 'complete' )
					GROUP BY c.comment_post_ID",
				implode( ',', $courses_ids )
			),
			'OBJECT_K'
		);
	}

}
