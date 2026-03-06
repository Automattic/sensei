<?php
/**
 * File containing the Comments_Based_Grading_Queries class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Grading;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Grading_Queries.
 *
 * Comments-based implementation of grading queries.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Grading_Queries implements Grading_Queries_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Comments_Based_Grading_Queries constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Count statuses for a given type.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments: type (course|lesson), post__in, post_id, user_id, query.
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array {
		/**
		 * Filter fires inside Sensei_Grading::count_statuses
		 *
		 * Alter the post_in array to determine which posts the comment query should be limited to.
		 *
		 * @since 1.8.0
		 *
		 * @hook sensei_count_statuses_args
		 *
		 * @param {array} $args Array of arguments for the query.
		 * @return {array} Filtered arguments.
		 */
		$args = apply_filters( 'sensei_count_statuses_args', $args );

		$type = 'course' === $args['type'] ? 'sensei_course_status' : 'sensei_lesson_status';

		$cache_key = 'sensei-statuses-' . md5( wp_json_encode( $args ) );

		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT comment_approved, COUNT( * ) AS total FROM {$this->wpdb->comments} WHERE comment_type = %s ",
			$type
		);

		// Restrict to specific posts.
		if ( isset( $args['post__in'] ) && ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$post__in_placeholder = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $this->wpdb->prepare( " AND comment_post_ID IN ( $post__in_placeholder )", $args['post__in'] );
		} elseif ( ! empty( $args['post_id'] ) ) {
			$query .= $this->wpdb->prepare( ' AND comment_post_ID = %d', $args['post_id'] );
		}

		// Restrict to specific users.
		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$user_id_placeholder = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $this->wpdb->prepare( " AND user_id IN ( $user_id_placeholder )", $args['user_id'] );
		} elseif ( ! empty( $args['user_id'] ) ) {
			$query .= $this->wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
		}

		// Append raw SQL query.
		if ( isset( $args['query'] ) ) {
			$query .= $args['query'];
		}

		$query .= ' GROUP BY comment_approved';

		$counts = wp_cache_get( $cache_key, 'counts' );
		if ( false === $counts ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL prepared in advance.
			$results = (array) $this->wpdb->get_results( $query, ARRAY_A );
			$counts  = array_fill_keys( $this->get_valid_statuses( $type ), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['comment_approved'] ] = $row['total'];
			}
			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		$counts = $this->ensure_default_status_keys( $counts );

		/**
		 * Filter the counts of statuses for a given type.
		 *
		 * @hook sensei_count_statuses
		 *
		 * @param {array}  $counts Array of counts for each status.
		 * @param {string} $type   Type of status to count: sensei_course_status or sensei_lesson_status.
		 * @return {array} Filtered counts.
		 */
		return apply_filters( 'sensei_count_statuses', $counts, $type );
	}

	/**
	 * Get the sum of all user grades for a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course ID.
	 * @return int Sum of grades.
	 */
	public function get_course_users_grades_sum( int $course_id ): int {
		$lesson_ids = \Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		if ( ! $lesson_ids ) {
			return 0;
		}

		$lesson_ids_placeholder = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders created dynamically.
		$sum_of_all_grades = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT SUM({$this->wpdb->commentmeta}.meta_value) AS meta_sum
				FROM {$this->wpdb->comments} INNER JOIN {$this->wpdb->commentmeta} ON ( {$this->wpdb->comments}.comment_ID = {$this->wpdb->commentmeta}.comment_id )
				WHERE {$this->wpdb->comments}.comment_type IN ('sensei_lesson_status') AND {$this->wpdb->comments}.comment_approved IN ('graded', 'passed', 'failed') AND ( {$this->wpdb->commentmeta}.meta_key = 'grade')
				AND {$this->wpdb->comments}.comment_post_ID IN ({$lesson_ids_placeholder}) ",
				$lesson_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared

		return $sum_of_all_grades;
	}

	/**
	 * Get average grade across all courses.
	 *
	 * @since $$next-version$$
	 *
	 * @return float Average grade.
	 */
	public function get_courses_average_grade(): float {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$result = $this->wpdb->get_row(
			"SELECT AVG(course_average) as courses_average
			FROM (
				SELECT AVG(cm.meta_value) as course_average
				FROM {$this->wpdb->comments} c
				INNER JOIN {$this->wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
				INNER JOIN {$this->wpdb->postmeta} course ON c.comment_post_ID = course.post_id
				INNER JOIN {$this->wpdb->postmeta} has_questions ON c.comment_post_ID = has_questions.post_id
				INNER JOIN {$this->wpdb->posts} p ON p.ID = course.meta_value
				WHERE c.comment_type = 'sensei_lesson_status'
					AND c.comment_approved IN ( 'graded', 'passed', 'failed' )
					AND cm.meta_key = 'grade'
					AND course.meta_key = '_lesson_course'
					AND course.meta_value <> ''
					AND has_questions.meta_key = '_quiz_has_questions'
				GROUP BY course.meta_value
			) averages_by_course"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return floatval( $result->courses_average ?? 0 );
	}

	/**
	 * Get grading items for the grading list table.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return array Array of comment objects.
	 */
	public function get_grading_items( array $args ): array {
		$statuses = \Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}
		return $statuses;
	}

	/**
	 * Count grading items matching the given arguments.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return int Count of matching items.
	 */
	public function count_grading_items( array $args ): int {
		return (int) \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);
	}

	/**
	 * Get the grade for a grading item.
	 *
	 * @since $$next-version$$
	 *
	 * @param object $item A comment object.
	 * @return string|null The grade value.
	 */
	public function get_item_grade( object $item ): ?string {
		$grade = get_comment_meta( $item->comment_ID, 'grade', true );
		return '' !== $grade ? $grade : null;
	}

	/**
	 * Get valid statuses for a given comment type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $type The comment type (sensei_course_status or sensei_lesson_status).
	 * @return array Array of valid statuses.
	 */
	private function get_valid_statuses( string $type ): array {
		switch ( $type ) {
			case 'sensei_course_status':
				return array(
					'in-progress',
					'complete',
				);

			case 'sensei_lesson_status':
				return array(
					'in-progress',
					'complete',
					'ungraded',
					'graded',
					'passed',
					'failed',
				);

			default:
				return array();
		}
	}

	/**
	 * Ensure default status keys exist in the counts array.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $counts The counts array.
	 * @return array The counts array with default keys.
	 */
	private function ensure_default_status_keys( array $counts ): array {
		$defaults = array(
			'graded'      => 0,
			'ungraded'    => 0,
			'passed'      => 0,
			'failed'      => 0,
			'in-progress' => 0,
			'complete'    => 0,
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $counts[ $key ] ) ) {
				$counts[ $key ] = $value;
			}
		}

		return $counts;
	}
}
