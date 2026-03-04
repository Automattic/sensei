<?php
/**
 * File containing the Progress_Aggregate_Query class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Query;

use Sensei\Internal\Services\Progress_Storage_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Progress_Aggregate_Query.
 *
 * Routes aggregate progress queries to the appropriate storage backend
 * (comments or HPPS tables) based on current storage settings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Progress_Aggregate_Query {

	/**
	 * Get the sum of all user grades for the given course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	public function sum_grades( int $course_id ): int {
		if ( Progress_Storage_Settings::is_tables_repository() ) {
			return $this->sum_grades_from_tables( $course_id );
		}

		return $this->sum_grades_from_comments( $course_id );
	}

	/**
	 * Sum grades from WordPress comments.
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	private function sum_grades_from_comments( int $course_id ): int {
		global $wpdb;

		$lesson_ids = \Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		if ( ! $lesson_ids ) {
			return 0;
		}

		$lesson_ids_placeholder = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$sum = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM({$wpdb->commentmeta}.meta_value) AS meta_sum
				FROM {$wpdb->comments}  INNER JOIN {$wpdb->commentmeta}  ON ( {$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id )
				WHERE {$wpdb->comments}.comment_type IN ('sensei_lesson_status') AND {$wpdb->comments}.comment_approved IN ('graded', 'passed', 'failed') AND ( {$wpdb->commentmeta}.meta_key = 'grade')
				AND {$wpdb->comments}.comment_post_ID IN ({$lesson_ids_placeholder}) ",
				$lesson_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return $sum;
	}

	/**
	 * Sum grades from HPPS custom tables.
	 *
	 * Queries sensei_lms_quiz_submissions.final_grade for quizzes belonging to
	 * lessons in the given course, filtered to only include submissions where
	 * the quiz progress status is graded, passed, or failed.
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	private function sum_grades_from_tables( int $course_id ): int {
		global $wpdb;

		$lesson_ids = \Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		if ( ! $lesson_ids ) {
			return 0;
		}

		$lesson_ids_placeholder = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		$progress_table    = $wpdb->prefix . 'sensei_lms_progress';
		$submissions_table = $wpdb->prefix . 'sensei_lms_quiz_submissions';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders created dynamically.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$sum = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(qs.final_grade)
				FROM {$submissions_table} qs
				INNER JOIN {$wpdb->posts} q ON qs.quiz_id = q.ID
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
