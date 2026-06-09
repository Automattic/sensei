<?php
/**
 * File containing the Comments_Based_Reports_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Reports_Listing_Service.
 *
 * Comments-based implementation of the Reports_Listing_Service_Interface.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Comments_Based_Reports_Listing_Service implements Reports_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		return $this->query_activity( $args, 'grade' );
	}

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		return $this->query_activity( $args, 'percent' );
	}

	/**
	 * Get lesson progress for one user in a course.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return Reports_Item|null
	 */
	public function get_user_lesson_progress( array $args ): ?Reports_Item {
		$lesson_status = \Sensei_Utils::sensei_check_for_activity( $args, true );

		if ( empty( $lesson_status ) || ! $lesson_status instanceof \WP_Comment ) {
			return null;
		}

		return $this->item_from_comment( $lesson_status, 'grade' );
	}

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		return $this->query_activity( $args, 'percent' );
	}

	/**
	 * Count students with activity on a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return int
	 */
	public function get_lesson_student_count( array $args ): int {
		return (int) \Sensei_Utils::sensei_check_for_activity( $args );
	}

	/**
	 * Count students who completed a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return int
	 */
	public function get_lesson_completion_count( array $args ): int {
		return (int) \Sensei_Utils::sensei_check_for_activity( $args );
	}

	/**
	 * Get the average quiz grade for a lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return float|null
	 */
	public function get_lesson_average_grade( array $args ): ?float {
		global $wpdb;

		$post_id  = (int) ( $args['post_id'] ?? 0 );
		$type     = (string) ( $args['type'] ?? 'sensei_lesson_status' );
		$meta_key = (string) ( $args['meta_key'] ?? 'grade' );
		$statuses = isset( $args['status'] ) ? (array) $args['status'] : array();

		if ( $post_id <= 0 || empty( $statuses ) ) {
			return null;
		}

		$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $status_placeholders is a list of %s; WP 6.4 changed WP_Comment_Query to use get_col(), so a comments_clauses-based aggregate is unreliable.
		$avg = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(cm.meta_value)
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->commentmeta} cm
				   ON cm.comment_id = c.comment_ID AND cm.meta_key = %s
				 WHERE c.comment_post_ID = %d
				   AND c.comment_type = %s
				   AND c.comment_approved IN ( {$status_placeholders} )",
				array_merge( array( $meta_key, $post_id, $type ), $statuses )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		Utils::log_query_error( $wpdb, 'Comments-based lesson average grade' );

		return null !== $avg ? round( (float) $avg, 2 ) : null;
	}

	/**
	 * Run a paginated activity query and map results to Reports_Item objects.
	 *
	 * @param array  $args      Activity args (see interface).
	 * @param string $meta_kind Numeric meta field to read: 'grade' or 'percent'.
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	private function query_activity( array $args, string $meta_kind ): array {
		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 0;
		if ( $number > 0 && (int) $total_count > 0 && $offset >= (int) $total_count ) {
			$last_page      = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$args['offset'] = $last_page * $number;
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}
			$items[] = $this->item_from_comment( $comment, $meta_kind );
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Build a Reports_Item from a WP_Comment row.
	 *
	 * @param \WP_Comment $comment   The activity comment.
	 * @param string      $meta_kind Numeric meta field to read: 'grade' or 'percent'.
	 * @return Reports_Item
	 */
	private function item_from_comment( \WP_Comment $comment, string $meta_kind ): Reports_Item {
		$start_date = get_comment_meta( (int) $comment->comment_ID, 'start', true );
		$grade      = null;
		$percent    = null;

		if ( 'grade' === $meta_kind ) {
			$grade_raw = get_comment_meta( (int) $comment->comment_ID, 'grade', true );
			$grade     = '' !== $grade_raw ? (float) $grade_raw : null;
		} else {
			$percent_raw = get_comment_meta( (int) $comment->comment_ID, 'percent', true );
			$percent     = '' !== $percent_raw ? (float) $percent_raw : null;
		}

		return new Reports_Item(
			(int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$comment->comment_approved,
			$start_date ? $start_date : null,
			$comment->comment_date ? $comment->comment_date : null,
			$grade,
			$percent
		);
	}
}
