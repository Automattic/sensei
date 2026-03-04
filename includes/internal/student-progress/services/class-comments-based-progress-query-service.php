<?php
/**
 * File containing the Comments_Based_Progress_Query_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Progress_Query_Service.
 *
 * Queries student progress aggregate data from WordPress comments.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Progress_Query_Service implements Progress_Query_Service_Interface {

	/**
	 * Get the sum of all user grades for the given course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of all grades.
	 */
	public function get_course_users_grades_sum( int $course_id ): int {
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
}
