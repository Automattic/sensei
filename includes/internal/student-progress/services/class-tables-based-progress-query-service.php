<?php
/**
 * File containing the Tables_Based_Progress_Query_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Progress_Query_Service.
 *
 * Queries student progress aggregate data from HPPS custom tables.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Progress_Query_Service implements Progress_Query_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Progress_Query_Service constructor.
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the sum of all user grades for the given course.
	 *
	 * Queries sensei_lms_quiz_submissions.final_grade for quizzes belonging to
	 * lessons in the given course, filtered to only include submissions where
	 * the quiz progress status is graded, passed, or failed.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	public function get_course_users_grades_sum( int $course_id ): int {
		$lesson_ids = \Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		if ( ! $lesson_ids ) {
			return 0;
		}

		$lesson_ids_placeholder = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		$progress_table    = $this->wpdb->prefix . 'sensei_lms_progress';
		$submissions_table = $this->wpdb->prefix . 'sensei_lms_quiz_submissions';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders created dynamically.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$sum = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT SUM(qs.final_grade)
				FROM {$submissions_table} qs
				INNER JOIN {$this->wpdb->posts} q ON qs.quiz_id = q.ID
				INNER JOIN {$progress_table} qp ON qp.post_id = qs.quiz_id AND qp.user_id = qs.user_id AND qp.type = 'quiz'
				WHERE q.post_parent IN ({$lesson_ids_placeholder})
				AND qp.status IN ('graded', 'passed', 'failed')
				AND qs.final_grade IS NOT NULL",
				$lesson_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared

		return $sum;
	}
}
