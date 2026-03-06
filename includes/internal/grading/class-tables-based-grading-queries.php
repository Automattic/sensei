<?php
/**
 * File containing the Tables_Based_Grading_Queries class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Grading;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Grading_Queries.
 *
 * Tables-based implementation of grading queries using custom progress and quiz submission tables.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Grading_Queries implements Grading_Queries_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Grading_Queries constructor.
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

		$type = $args['type'];

		$cache_key = 'sensei-statuses-' . md5( wp_json_encode( $args ) );

		$progress_table = $this->wpdb->prefix . 'sensei_lms_progress';

		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT status, COUNT( * ) AS total FROM {$progress_table} WHERE type = %s ",
			$type
		);

		// Restrict to specific posts.
		if ( isset( $args['post__in'] ) && ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$post__in_placeholder = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $this->wpdb->prepare( " AND post_id IN ( $post__in_placeholder )", $args['post__in'] );
		} elseif ( ! empty( $args['post_id'] ) ) {
			$query .= $this->wpdb->prepare( ' AND post_id = %d', $args['post_id'] );
		}

		// Restrict to specific users.
		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$user_id_placeholder = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $this->wpdb->prepare( " AND user_id IN ( $user_id_placeholder )", $args['user_id'] );
		} elseif ( ! empty( $args['user_id'] ) ) {
			$query .= $this->wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
		}

		// Translate raw SQL query from comments table to progress table.
		if ( isset( $args['query'] ) ) {
			$query .= str_replace( 'comment_post_ID', 'post_id', $args['query'] );
		}

		$query .= ' GROUP BY status';

		$counts = wp_cache_get( $cache_key, 'counts' );
		if ( false === $counts ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL prepared in advance.
			$results = (array) $this->wpdb->get_results( $query, ARRAY_A );
			$counts  = array_fill_keys( $this->get_valid_statuses( $type ), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['status'] ] = $row['total'];
			}
			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		$counts = $this->ensure_default_status_keys( $counts );

		// Use the comment type for the filter to maintain backward compatibility.
		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';

		/**
		 * Filter the counts of statuses for a given type.
		 *
		 * @hook sensei_count_statuses
		 *
		 * @param {array}  $counts Array of counts for each status.
		 * @param {string} $type   Type of status to count: sensei_course_status or sensei_lesson_status.
		 * @return {array} Filtered counts.
		 */
		return apply_filters( 'sensei_count_statuses', $counts, $comment_type );
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

		$submissions_table = $this->wpdb->prefix . 'sensei_lms_quiz_submissions';

		$lesson_ids_placeholder = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders created dynamically.
		$sum_of_all_grades = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT SUM(qs.final_grade) AS grade_sum
				FROM {$submissions_table} qs
				INNER JOIN {$this->wpdb->posts} quiz ON qs.quiz_id = quiz.ID
				WHERE quiz.post_parent IN ({$lesson_ids_placeholder})
				AND qs.final_grade IS NOT NULL",
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
		$submissions_table = $this->wpdb->prefix . 'sensei_lms_quiz_submissions';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance improvement.
		$result = $this->wpdb->get_row(
			"SELECT AVG(course_average) as courses_average
			FROM (
				SELECT AVG(qs.final_grade) as course_average
				FROM {$submissions_table} qs
				INNER JOIN {$this->wpdb->posts} quiz ON qs.quiz_id = quiz.ID
				INNER JOIN {$this->wpdb->postmeta} course ON quiz.post_parent = course.post_id
				INNER JOIN {$this->wpdb->postmeta} has_questions ON quiz.post_parent = has_questions.post_id
				INNER JOIN {$this->wpdb->posts} p ON p.ID = course.meta_value
				WHERE qs.final_grade IS NOT NULL
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
	 * Returns objects with WP_Comment-compatible properties for seamless integration
	 * with existing list table code.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return array Array of objects with comment-compatible properties.
	 */
	public function get_grading_items( array $args ): array {
		$progress_table    = $this->wpdb->prefix . 'sensei_lms_progress';
		$submissions_table = $this->wpdb->prefix . 'sensei_lms_quiz_submissions';

		$query = 'SELECT p.id AS comment_ID, p.status AS comment_approved, p.updated_at AS comment_date, p.user_id, p.post_id AS comment_post_ID, qs.final_grade AS grade'
			. " FROM {$progress_table} p"
			. " LEFT JOIN {$this->wpdb->posts} quiz ON quiz.post_parent = p.post_id AND quiz.post_type = 'quiz'"
			. " LEFT JOIN {$submissions_table} qs ON qs.quiz_id = quiz.ID AND qs.user_id = p.user_id"
			. $this->wpdb->prepare( ' WHERE p.type = %s', 'lesson' );

		$query .= $this->build_grading_items_where( $args );

		// Ordering.
		$orderby = $args['orderby'] ?? '';
		$order   = $args['order'] ?? 'DESC';
		$order   = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $order ) : 'DESC';

		$orderby_map = array(
			'title'       => 'p.user_id',
			'lesson'      => 'p.post_id',
			'updated'     => 'p.updated_at',
			'user_status' => 'p.status',
			'user_grade'  => 'qs.final_grade',
		);

		if ( ! empty( $orderby ) && isset( $orderby_map[ $orderby ] ) ) {
			$query .= ' ORDER BY ' . $orderby_map[ $orderby ] . ' ' . $order;
		} else {
			$query .= " ORDER BY p.updated_at {$order}";
		}

		// Pagination.
		if ( ! empty( $args['number'] ) ) {
			$query .= $this->wpdb->prepare( ' LIMIT %d', $args['number'] );
		}
		if ( ! empty( $args['offset'] ) ) {
			$query .= $this->wpdb->prepare( ' OFFSET %d', $args['offset'] );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query built dynamically above.
		return $this->wpdb->get_results( $query );
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
		$progress_table = $this->wpdb->prefix . 'sensei_lms_progress';

		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT COUNT(*) FROM {$progress_table} p WHERE p.type = %s",
			'lesson'
		);

		$query .= $this->build_grading_items_where( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query built dynamically above.
		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get the grade for a grading item.
	 *
	 * @since $$next-version$$
	 *
	 * @param object $item A grading item from get_grading_items.
	 * @return string|null The grade value.
	 */
	public function get_item_grade( object $item ): ?string {
		return isset( $item->grade ) && null !== $item->grade ? (string) $item->grade : null;
	}

	/**
	 * Build WHERE clause additions for grading items queries.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL WHERE additions.
	 */
	private function build_grading_items_where( array $args ): string {
		$where = '';

		// Filter by post.
		if ( ! empty( $args['post_id'] ) ) {
			$where .= $this->wpdb->prepare( ' AND p.post_id = %d', $args['post_id'] );
		} elseif ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$where .= $this->wpdb->prepare( " AND p.post_id IN ( {$placeholders} )", $args['post__in'] );
		}

		// Filter by user.
		if ( isset( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$where .= $this->wpdb->prepare( " AND p.user_id IN ( {$placeholders} )", $args['user_id'] );
		} elseif ( ! empty( $args['user_id'] ) ) {
			$where .= $this->wpdb->prepare( ' AND p.user_id = %d', $args['user_id'] );
		}

		// Filter by status.
		$status = $args['status'] ?? 'any';
		if ( 'any' !== $status ) {
			if ( is_array( $status ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $status ), '%s' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
				$where .= $this->wpdb->prepare( " AND p.status IN ( {$placeholders} )", $status );
			} else {
				$where .= $this->wpdb->prepare( ' AND p.status = %s', $status );
			}
		}

		return $where;
	}

	/**
	 * Get valid statuses for a given type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $type The progress type (course or lesson).
	 * @return array Array of valid statuses.
	 */
	private function get_valid_statuses( string $type ): array {
		switch ( $type ) {
			case 'course':
				return array(
					'in-progress',
					'complete',
				);

			case 'lesson':
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
