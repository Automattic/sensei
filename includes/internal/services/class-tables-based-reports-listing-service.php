<?php
/**
 * File containing the Tables_Based_Reports_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Reports_Listing_Service.
 *
 * Tables-based (HPPS) implementation of the Reports_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Reports_Listing_Service implements Reports_Listing_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the progress table name.
	 *
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Get the quiz submissions table name.
	 *
	 * @return string
	 */
	private function get_quiz_submissions_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_submissions';
	}

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		$where = " WHERE p.type = 'lesson'" . $this->build_filters( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . $where );
		Utils::log_query_error( $wpdb, 'Reports lesson students count' );

		$number = (int) ( $args['number'] ?? 0 );
		$offset = (int) ( $args['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		$order_clause = $this->build_order_clause( $args );
		$limit_clause = $number > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '';

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, COALESCE( q.status, p.status ) AS effective_status, p.started_at, p.completed_at, qs.final_grade AS grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id',
				$table,
				$table,
				$submissions_table
			)
			. $where
			. $order_clause
			. $limit_clause
		);
		Utils::log_query_error( $wpdb, 'Reports lesson students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Reports_Item(
				(int) $row->post_id,
				(int) $row->user_id,
				$row->effective_status,
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null !== $row->grade ? (float) $row->grade : null,
				null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $total_count,
		);
	}

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$course_id = (int) ( $args['post_id'] ?? 0 );
		$where     = " WHERE p.type = 'course'" . $this->build_filters( $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . $where );
		Utils::log_query_error( $wpdb, 'Reports course students count' );

		$number = (int) ( $args['number'] ?? 0 );
		$offset = (int) ( $args['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		$order_clause  = $this->build_order_clause( $args );
		$limit_clause  = $number > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '';
		$total_lessons = $this->get_course_lesson_count( $course_id );
		$completed_sql = $this->completed_statuses_sql();

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values; $completed_sql is derived from a class constant.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE('
				. '   ( SELECT COUNT(*) FROM %i lp'
				. '     LEFT JOIN %i lq ON lq.parent_post_id = lp.post_id AND lq.user_id = lp.user_id AND lq.type = \'quiz\''
				. '     WHERE lp.parent_post_id = p.post_id AND lp.user_id = p.user_id AND lp.type = \'lesson\''
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $completed_sql is derived from a class constant.
				. "     AND COALESCE( lq.status, lp.status ) IN ( {$completed_sql} )"
				. '   ) * 100.0 / NULLIF( %d, 0 ),'
				. '   0'
				. ' ) AS percent'
				. ' FROM %i p',
				$table,
				$table,
				$total_lessons,
				$table
			)
			. $where
			. $order_clause
			. $limit_clause
		);
		Utils::log_query_error( $wpdb, 'Reports course students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Reports_Item(
				(int) $row->post_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null,
				null !== $row->percent ? round( (float) $row->percent, 0 ) : null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $total_count,
		);
	}

	/**
	 * Get a single lesson's progress for one user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return Reports_Item|null
	 */
	public function get_user_lesson_progress( array $args ): ?Reports_Item {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		$post_id = (int) ( $args['post_id'] ?? 0 );
		$user_id = (int) ( $args['user_id'] ?? 0 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching handled by callers.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, COALESCE( q.status, p.status ) AS effective_status,'
				. ' p.started_at, p.completed_at, qs.final_grade AS grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id'
				. ' WHERE p.post_id = %d AND p.user_id = %d AND p.type = \'lesson\'',
				$table,
				$table,
				$submissions_table,
				$post_id,
				$user_id
			)
		);
		Utils::log_query_error( $wpdb, 'Reports user lesson progress' );

		if ( ! $row ) {
			return null;
		}

		return new Reports_Item(
			(int) $row->post_id,
			(int) $row->user_id,
			$row->effective_status,
			$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
			$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
			null !== $row->grade ? (float) $row->grade : null,
			null
		);
	}

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$user_id = (int) ( $args['user_id'] ?? 0 );
		$where   = " WHERE p.type = 'course'" . $this->build_filters( $args );

		if ( ! empty( $args['post_author'] ) ) {
			$where .= $wpdb->prepare(
				' AND p.post_id IN ( SELECT ID FROM %i WHERE post_author = %d )',
				$wpdb->posts,
				(int) $args['post_author']
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . $where );
		Utils::log_query_error( $wpdb, 'Reports user courses count' );

		$number = (int) ( $args['number'] ?? 0 );
		$offset = (int) ( $args['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		$order_clause = $this->build_order_clause( $args );
		$limit_clause = $number > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '';

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, p.status, p.started_at, p.completed_at FROM %i p',
				$table
			)
			. $where
			. $order_clause
			. $limit_clause
		);
		Utils::log_query_error( $wpdb, 'Reports user courses items' );

		$items         = array();
		$completed_sql = $this->completed_statuses_sql();
		foreach ( $rows as $row ) {
			$course_id     = (int) $row->post_id;
			$total_lessons = $this->get_course_lesson_count( $course_id );

			$percent = 0;
			if ( $total_lessons > 0 ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $completed_sql is derived from a class constant. Caching handled by callers.
				$completed_lessons = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i lp'
						. ' LEFT JOIN %i lq ON lq.parent_post_id = lp.post_id AND lq.user_id = lp.user_id AND lq.type = \'quiz\''
						. ' WHERE lp.parent_post_id = %d AND lp.user_id = %d AND lp.type = \'lesson\''
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $completed_sql is derived from a class constant.
						. " AND COALESCE( lq.status, lp.status ) IN ( {$completed_sql} )",
						$table,
						$table,
						$course_id,
						$user_id
					)
				);
				$percent = round( $completed_lessons * 100.0 / $total_lessons, 0 );
			}

			$items[] = new Reports_Item(
				$course_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null,
				(float) $percent
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $total_count,
		);
	}

	/**
	 * Get per-lesson aggregate stats for a course overview.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @return array[]
	 */
	public function get_lesson_aggregates( int $course_id ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();
		$completed_sql     = $this->completed_statuses_sql();

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $completed_sql is derived from a class constant. Caching handled by callers.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id AS lesson_id,'
				. ' COUNT( DISTINCT p.user_id ) AS student_count,'
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $completed_sql is derived from a class constant.
				. " COUNT( DISTINCT CASE WHEN COALESCE( q.status, p.status ) IN ( {$completed_sql} ) THEN p.user_id END ) AS completion_count,"
				. ' AVG( qs.final_grade ) AS average_grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id'
				. ' WHERE p.parent_post_id = %d AND p.type = \'lesson\''
				. ' GROUP BY p.post_id',
				$table,
				$table,
				$submissions_table,
				$course_id
			)
		);
		Utils::log_query_error( $wpdb, 'Reports lesson aggregates' );

		$aggregates = array();
		foreach ( $rows as $row ) {
			$avg_grade = null !== $row->average_grade ? round( (float) $row->average_grade, 2 ) : null;

			$aggregates[ (int) $row->lesson_id ] = array(
				'lesson_id'        => (int) $row->lesson_id,
				'student_count'    => (int) $row->student_count,
				'completion_count' => (int) $row->completion_count,
				'average_grade'    => $avg_grade,
			);
		}

		return $aggregates;
	}

	/**
	 * Translate the activity-args shape into shared SQL WHERE clauses.
	 *
	 * Applies post_id, user_id, status, and the meta_query entry with key 'start'
	 * (used by the course list table for start-date range filtering).
	 *
	 * @param array $args Activity args.
	 * @return string SQL clauses, each prefixed with ' AND '.
	 */
	private function build_filters( array $args ): string {
		$wpdb = $this->wpdb;
		$sql  = '';

		if ( ! empty( $args['post_id'] ) ) {
			$sql .= $wpdb->prepare( ' AND p.post_id = %d', (int) $args['post_id'] );
		}

		if ( isset( $args['user_id'] ) && '' !== $args['user_id'] && array() !== $args['user_id'] ) {
			if ( is_array( $args['user_id'] ) ) {
				$ids = array_map( 'intval', $args['user_id'] );
				if ( empty( $ids ) ) {
					// Force empty result.
					$sql .= ' AND 1 = 0';
				} else {
					$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$sql .= $wpdb->prepare( " AND p.user_id IN ( $placeholders )", $ids );
				}
			} else {
				$sql .= $wpdb->prepare( ' AND p.user_id = %d', (int) $args['user_id'] );
			}
		}

		if ( ! empty( $args['status'] ) && 'any' !== $args['status'] ) {
			$statuses = array_map( 'strval', (array) $args['status'] );
			if ( ! empty( $statuses ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$sql .= $wpdb->prepare( " AND p.status IN ( $placeholders )", $statuses );
			}
		}

		$sql .= $this->build_start_date_filter( $args );

		return $sql;
	}

	/**
	 * Translate a meta_query entry with key 'start' into started_at SQL.
	 *
	 * The course list table passes start-date range filters via meta_query
	 * (to remain compatible with the comments-based path). For tables-based
	 * storage the equivalent column is p.started_at.
	 *
	 * @param array $args Activity args.
	 * @return string SQL clause.
	 */
	private function build_start_date_filter( array $args ): string {
		if ( empty( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
			return '';
		}

		$wpdb = $this->wpdb;
		$sql  = '';

		foreach ( $args['meta_query'] as $outer ) {
			if ( ! is_array( $outer ) ) {
				continue;
			}
			foreach ( $outer as $clause ) {
				if ( ! is_array( $clause ) || ( $clause['key'] ?? '' ) !== 'start' ) {
					continue;
				}
				$compare = $clause['compare'] ?? '=';
				$value   = $clause['value'] ?? '';
				if ( in_array( $compare, array( '>=', '<=', '>', '<', '=' ), true ) && '' !== $value ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $compare whitelisted above.
					$sql .= $wpdb->prepare( " AND p.started_at {$compare} %s", $value );
				}
			}
		}

		return $sql;
	}

	/**
	 * Build a comma-separated, SQL-quoted list of statuses considered completed,
	 * derived from Grading_Item::COMPLETED_STATUSES. Values are class constants,
	 * not user input.
	 *
	 * @return string
	 */
	private function completed_statuses_sql(): string {
		return "'" . implode( "','", Reports_Item::COMPLETED_STATUSES ) . "'";
	}

	/**
	 * Get the total number of lessons in a course.
	 *
	 * @param int $course_id Course post ID.
	 * @return int
	 */
	private function get_course_lesson_count( int $course_id ): int {
		$lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		return count( $lessons );
	}

	/**
	 * Build ORDER BY clause.
	 *
	 * @param array $args Query arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_order_clause( array $args ): string {
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = $args['orderby'] ?? '';

		$orderby_map = array(
			'comment_date' => 'p.completed_at',
			'started'      => 'p.started_at',
			'completed'    => 'p.completed_at',
		);

		/** Sanitized column name. @var string $column */
		$column = esc_sql( $orderby_map[ $orderby ] ?? 'p.started_at' );

		return " ORDER BY {$column} {$order}";
	}
}
