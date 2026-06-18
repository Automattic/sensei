<?php
/**
 * File containing the Comments_Based_Progress_Aggregation_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Progress_Aggregation_Service.
 *
 * Comments-based (legacy) implementation of progress aggregation.
 * Queries wp_comments to count progress records grouped by status.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Comments_Based_Progress_Aggregation_Service implements Progress_Aggregation_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since 4.26.0
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Count progress records grouped by status.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args {
	 *     Arguments for the query.
	 *
	 *     @type string    $type                         'course' or 'lesson'.
	 *     @type array     $post__in                     Restrict to specific post IDs.
	 *     @type int       $post_id                      Restrict to a single post ID.
	 *     @type int|array $user_id                      Restrict to specific user IDs.
	 *     @type string[]  $exclude_user_login_prefixes  User login prefixes to exclude.
	 *     @type string[]  $include_statuses_override    Statuses that bypass user exclusion.
	 *     @type string    $query                        Raw SQL to append (backward compat).
	 * }
	 * @return array Associative array of status => count.
	 */
	public function count_statuses( array $args ): array {
		if ( empty( $args['type'] ) || ! in_array( $args['type'], array( 'course', 'lesson' ), true ) ) {
			_doing_it_wrong(
				__METHOD__,
				'The "type" argument must be "course" or "lesson".',
				'4.26.0'
			);
			return array();
		}

		$wpdb         = $this->wpdb;
		$comment_type = 'course' === $args['type'] ? 'sensei_course_status' : 'sensei_lesson_status';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb.
		$query = $wpdb->prepare( "SELECT comment_approved, COUNT( * ) AS total FROM {$wpdb->comments} INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID AND {$wpdb->posts}.post_status IN ( 'publish', 'private' ) WHERE comment_type = %s", $comment_type );

		$query .= $this->build_post_filter_clause( $args );
		$query .= $this->build_user_filter_clause( $args );
		$query .= $this->build_user_exclusion_clause( $args );

		if ( isset( $args['query'] ) ) {
			$query .= $args['query'];
		}

		$query .= ' GROUP BY comment_approved';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$results = (array) $wpdb->get_results( $query, ARRAY_A );
		Utils::log_query_error( $wpdb, 'Comments-based status counts' );

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['comment_approved'] ] = (int) $row['total'];
		}

		return $counts;
	}

	/**
	 * Get aggregate totals for a set of lessons.
	 *
	 * @since 4.26.0
	 *
	 * @param int[] $lesson_ids Array of lesson post IDs.
	 * @return array Associative array with keys: unique_student_count, lesson_start_count, lesson_completed_count, days_to_complete_count, days_to_complete_sum.
	 */
	public function get_lesson_totals( array $lesson_ids ): array {
		$defaults = [
			'unique_student_count'   => 0,
			'lesson_start_count'     => 0,
			'lesson_completed_count' => 0,
			'days_to_complete_count' => 0,
			'days_to_complete_sum'   => 0,
		];

		if ( empty( $lesson_ids ) ) {
			return $defaults;
		}

		$wpdb           = $this->wpdb;
		$placeholders   = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );
		$completed      = "'" . implode( "','", Grading_Item::COMPLETED_STATUSES ) . "'";
		$has_completion = "'" . implode( "','", Grading_Item::STATUSES_WITH_COMPLETION_DATE ) . "'";

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from wpdb. Placeholders created dynamically. Date format string uses literal %s for MySQL STR_TO_DATE.
		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT(lesson_students.user_id)) unique_student_count
			, COUNT(lesson_students.comment_id) lesson_start_count
			, SUM(IF(lesson_students.comment_approved IN ($completed), 1, 0)) lesson_completed_count
			, SUM(IF(lesson_students.comment_approved IN ($has_completion), 1, 0)) days_to_complete_count
			, SUM(IF(lesson_students.comment_approved IN ($has_completion), ABS( DATEDIFF( STR_TO_DATE( lesson_start.meta_value, %s ), lesson_students.comment_date ) ) + 1, 0)) days_to_complete_sum
			FROM {$wpdb->comments} lesson_students
			INNER JOIN {$wpdb->posts} post ON post.ID = lesson_students.comment_post_ID AND post.post_status IN ( 'publish', 'private' )
			LEFT JOIN {$wpdb->commentmeta} lesson_start ON lesson_start.comment_id = lesson_students.comment_id
			WHERE lesson_start.meta_key = 'start' AND lesson_students.comment_post_id IN ( $placeholders )",
			array_merge( [ '%Y-%m-%d %H:%i:%s' ], $lesson_ids )
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL prepared in advance. Caching handled by callers.
		$row = $wpdb->get_row( $query );
		Utils::log_query_error( $wpdb, 'Comments-based lesson totals' );

		if ( ! $row ) {
			return $defaults;
		}

		return [
			'unique_student_count'   => (int) $row->unique_student_count,
			'lesson_start_count'     => (int) $row->lesson_start_count,
			'lesson_completed_count' => (int) $row->lesson_completed_count,
			'days_to_complete_count' => (int) $row->days_to_complete_count,
			'days_to_complete_sum'   => (int) $row->days_to_complete_sum,
		];
	}

	/**
	 * Count ungraded quiz submissions whose lesson is publicly available.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Optional restrictions; see interface.
	 * @return int Number of ungraded quiz submissions for live (publish or private) lessons.
	 */
	public function count_ungraded_quizzes( array $args = array() ): int {
		$wpdb = $this->wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from wpdb. comment_type/comment_approved values are constants.
		$query = "SELECT COUNT(*) FROM {$wpdb->comments}
			INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID AND {$wpdb->posts}.post_status IN ( 'publish', 'private' )
			WHERE {$wpdb->comments}.comment_type = 'sensei_lesson_status' AND {$wpdb->comments}.comment_approved = 'ungraded'";

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$query .= $wpdb->prepare( " AND {$wpdb->comments}.comment_post_ID IN ( $placeholders )", $args['post__in'] );
		}

		$query .= $this->build_user_exclusion_clause( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL built from literals only.
		$count = (int) $wpdb->get_var( $query );
		Utils::log_query_error( $wpdb, 'Comments-based ungraded quizzes count' );

		return $count;
	}

	/**
	 * Build SQL clause for filtering by post ID(s).
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_post_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		// Prefer post_id (single lesson filter) over post__in (course lessons)
		// so that counts reflect the specific lesson when both are set.
		if ( ! empty( $args['post_id'] ) ) {
			return $wpdb->prepare( ' AND comment_post_ID = %d', $args['post_id'] );
		}

		if ( ! empty( $args['post__in'] ) && is_array( $args['post__in'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['post__in'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND comment_post_ID IN ( $placeholders )", $args['post__in'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for filtering by user ID(s).
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_filter_clause( array $args ): string {
		$wpdb = $this->wpdb;

		if ( ! empty( $args['user_id'] ) && is_array( $args['user_id'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['user_id'] ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			return $wpdb->prepare( " AND user_id IN ( $placeholders )", $args['user_id'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			return $wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
		}

		return '';
	}

	/**
	 * Build SQL clause for excluding users by login prefix.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Query arguments.
	 * @return string SQL clause.
	 */
	private function build_user_exclusion_clause( array $args ): string {
		if ( empty( $args['exclude_user_login_prefixes'] ) ) {
			return '';
		}

		$prefixes = array_filter( $args['exclude_user_login_prefixes'] );
		if ( empty( $prefixes ) ) {
			return '';
		}

		$wpdb             = $this->wpdb;
		$not_like_clauses = [];
		foreach ( $prefixes as $prefix ) {
			$escaped_prefix     = $wpdb->esc_like( $prefix );
			$not_like_clauses[] = $wpdb->prepare( 'comment_author NOT LIKE %s', $escaped_prefix . '%' );
		}

		$exclusion_sql = '( ' . implode( ' AND ', $not_like_clauses ) . ' )';

		if ( ! empty( $args['include_statuses_override'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
			$override_sql = $wpdb->prepare( "comment_approved IN ( $status_placeholders )", $args['include_statuses_override'] );
			return " AND ( $exclusion_sql OR $override_sql )";
		}

		return " AND $exclusion_sql";
	}
}
