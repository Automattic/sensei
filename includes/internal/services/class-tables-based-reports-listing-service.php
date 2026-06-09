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
 * @since 4.26.0
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
	 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();
		$post_id           = (int) ( $args['post_id'] ?? 0 );

		$where      = " WHERE p.type = 'lesson'" . $this->build_filters( $args );
		$pagination = $this->build_pagination( $where, $args );

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each student's lesson progress with their effective quiz/lesson status and grade.
				// Join quiz progress (q) to get the quiz-aware status via COALESCE: if a quiz
				// exists for this lesson, its status takes precedence over the lesson status.
				// Join quiz submissions (qs) to get the final_grade when available.
				'SELECT p.user_id, COALESCE( q.status, p.status ) AS effective_status, p.started_at, p.completed_at, qs.final_grade AS grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i pm ON pm.post_id = p.post_id AND pm.meta_key = \'_lesson_quiz\' AND pm.meta_value > 0'
				. ' LEFT JOIN %i q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id',
				$table,
				$wpdb->postmeta,
				$table,
				$submissions_table
			)
			. $where
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		Utils::log_query_error( $wpdb, 'Reports lesson students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Reports_Item(
				$post_id,
				(int) $row->user_id,
				$row->effective_status ?? 'in-progress',
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null !== $row->grade ? (float) $row->grade : null,
				null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $pagination['total_count'],
		);
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
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$course_id     = (int) ( $args['post_id'] ?? 0 );
		$where         = " WHERE p.type = 'course'" . $this->build_filters( $args );
		$pagination    = $this->build_pagination( $where, $args );
		$total_lessons = count( Sensei()->course->course_lessons( $course_id, 'publish', 'ids' ) );

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each student's course progress with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				// The total_lessons denominator is pre-computed in PHP since all rows share the same course.
				'SELECT p.user_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( %d, 0 ), 0 ) AS percent'
				. ' FROM %i p'
				// Derived table: count completed published lessons per student in this course.
				. ' LEFT JOIN ('
				. '   SELECT lp.user_id, COUNT(*) AS cnt'
				. '   FROM %i lp'
				. '   INNER JOIN %i pm ON pm.post_id = lp.post_id AND pm.meta_key = \'_lesson_course\' AND pm.meta_value = %d'
				. '   INNER JOIN %i lpost ON lpost.ID = lp.post_id AND lpost.post_status = \'publish\''
				. '   WHERE lp.type = \'lesson\' AND lp.status = \'complete\''
				. '   GROUP BY lp.user_id'
				. ' ) completed ON completed.user_id = p.user_id',
				$total_lessons,
				$table,
				$table,
				$wpdb->postmeta,
				$course_id,
				$wpdb->posts
			)
			. $where
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		Utils::log_query_error( $wpdb, 'Reports course students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Reports_Item(
				$course_id,
				(int) $row->user_id,
				$row->status ?? 'in-progress',
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null,
				null !== $row->percent ? round( (float) $row->percent, 0 ) : null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $pagination['total_count'],
		);
	}

	/**
	 * Get a single lesson's progress for one user.
	 *
	 * @since 4.26.0
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
				// Fetch one lesson's progress for one user, with quiz-aware effective status.
				// Same JOIN pattern as get_lesson_students but for a single row.
				'SELECT COALESCE( q.status, p.status ) AS effective_status,'
				. ' p.started_at, p.completed_at, qs.final_grade AS grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i pm ON pm.post_id = p.post_id AND pm.meta_key = \'_lesson_quiz\' AND pm.meta_value > 0'
				. ' LEFT JOIN %i q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id'
				. ' WHERE p.post_id = %d AND p.user_id = %d AND p.type = \'lesson\'',
				$table,
				$wpdb->postmeta,
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
			$post_id,
			$user_id,
			$row->effective_status ?? 'in-progress',
			$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
			$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
			null !== $row->grade ? (float) $row->grade : null,
			null
		);
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

		$pagination = $this->build_pagination( $where, $args );

		/** Query result rows. @var object[] $rows */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clauses are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each course's progress for this user with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				'SELECT p.post_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( total.cnt, 0 ), 0 ) AS percent'
				. ' FROM %i p'
				// Derived table: count completed published lessons per course for this user.
				// Lessons are mapped to courses via the _lesson_course postmeta.
				. ' LEFT JOIN ('
				. '   SELECT pm.meta_value AS course_id, COUNT(*) AS cnt'
				. '   FROM %i lp'
				. '   INNER JOIN %i pm ON pm.post_id = lp.post_id AND pm.meta_key = \'_lesson_course\''
				. '   INNER JOIN %i lpost ON lpost.ID = lp.post_id AND lpost.post_status = \'publish\''
				. '   WHERE lp.type = \'lesson\' AND lp.user_id = %d'
				. '   AND lp.status = \'complete\''
				. '   GROUP BY pm.meta_value'
				. ' ) completed ON completed.course_id = p.post_id'
				// Derived table: count total published lessons per course
				// via the _lesson_course postmeta that maps each lesson to its course.
				. ' LEFT JOIN ('
				. '   SELECT pm.meta_value AS course_id, COUNT(*) AS cnt'
				. '   FROM %i pm'
				. '   INNER JOIN %i lpost ON lpost.ID = pm.post_id AND lpost.post_status = \'publish\''
				. '   WHERE pm.meta_key = \'_lesson_course\''
				. '   GROUP BY pm.meta_value'
				. ' ) total ON total.course_id = p.post_id',
				$table,
				$table,
				$wpdb->postmeta,
				$wpdb->posts,
				$user_id,
				$wpdb->postmeta,
				$wpdb->posts
			)
			. $where
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		Utils::log_query_error( $wpdb, 'Reports user courses items' );

		$items = array();
		/** Query result row. @var object $row */
		foreach ( $rows as $row ) {
			$items[] = new Reports_Item(
				(int) $row->post_id,
				$user_id,
				$row->status ?? 'in-progress',
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null,
				null !== $row->percent ? round( (float) $row->percent, 0 ) : null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => $pagination['total_count'],
		);
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
		$wpdb    = $this->wpdb;
		$table   = $this->get_progress_table_name();
		$post_id = (int) ( $args['post_id'] ?? 0 );
		$status  = $args['status'] ?? 'any';

		$where = $wpdb->prepare( ' WHERE p.post_id = %d AND p.type = \'lesson\'', $post_id );
		if ( 'any' !== $status ) {
			$status_sql = $this->statuses_sql( $args );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $status_sql is built from escaped args.
			$where .= " AND p.status IN ( {$status_sql} )";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT( DISTINCT p.user_id ) FROM %i p', $table ) . $where );
		Utils::log_query_error( $wpdb, 'Reports lesson student count' );

		return (int) $count;
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
		$wpdb       = $this->wpdb;
		$table      = $this->get_progress_table_name();
		$post_id    = (int) ( $args['post_id'] ?? 0 );
		$status_sql = $this->statuses_sql( $args );

		// Count students whose effective status (quiz status when available,
		// lesson status otherwise) is in the caller-provided set.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $status_sql is built from escaped args.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( DISTINCT p.user_id )'
				. ' FROM %i p'
				. ' LEFT JOIN %i pm ON pm.post_id = p.post_id AND pm.meta_key = \'_lesson_quiz\' AND pm.meta_value > 0'
				. ' LEFT JOIN %i q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' WHERE p.post_id = %d AND p.type = \'lesson\''
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $status_sql is built from escaped args.
				. " AND ( q.status IN ( {$status_sql} ) OR ( q.post_id IS NULL AND p.status IN ( {$status_sql} ) ) )",
				$table,
				$wpdb->postmeta,
				$table,
				$post_id
			)
		);
		Utils::log_query_error( $wpdb, 'Reports lesson completion count' );

		return (int) $count;
	}

	/**
	 * Get the average quiz grade for a lesson.
	 *
	 * Only `post_id` and `status` from $args are honored; `type` and `meta_key` are ignored
	 * because the tables schema queries `sensei_lms_progress` + `sensei_lms_quiz_submissions`
	 * directly rather than commentmeta.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return float|null
	 */
	public function get_lesson_average_grade( array $args ): ?float {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();
		$post_id           = (int) ( $args['post_id'] ?? 0 );
		$status_sql        = $this->statuses_sql( $args );

		// Filter by the caller-provided statuses on the effective quiz status,
		// then average the grade from quiz_submissions.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $status_sql is built from escaped args.
		$avg = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT AVG( qs.final_grade )'
				. ' FROM %i p'
				. ' LEFT JOIN %i pm ON pm.post_id = p.post_id AND pm.meta_key = \'_lesson_quiz\' AND pm.meta_value > 0'
				. ' LEFT JOIN %i q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id'
				. ' WHERE p.post_id = %d AND p.type = \'lesson\' AND qs.final_grade IS NOT NULL'
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $status_sql is built from escaped args.
				. " AND ( q.status IN ( {$status_sql} ) OR ( q.post_id IS NULL AND p.status IN ( {$status_sql} ) ) )",
				$table,
				$wpdb->postmeta,
				$table,
				$submissions_table,
				$post_id
			)
		);
		Utils::log_query_error( $wpdb, 'Reports lesson average grade' );

		return null !== $avg ? round( (float) $avg, 2 ) : null;
	}

	/**
	 * Run the count query, snap the offset if it exceeds the total, and build
	 * ORDER BY / LIMIT clauses. Shared by all paginated query methods.
	 *
	 * @param string $where SQL WHERE clause (already prepared).
	 * @param array  $args  Activity args containing number, offset, orderby, order.
	 * @return array{ total_count: int, order_clause: string, limit_clause: string }
	 */
	private function build_pagination( string $where, array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . $where );
		Utils::log_query_error( $wpdb, 'Reports pagination count' );

		$number = (int) ( $args['number'] ?? 0 );
		$offset = (int) ( $args['offset'] ?? 0 );
		if ( $number > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$offset    = $last_page * $number;
		}

		return array(
			'total_count'  => $total_count,
			'order_clause' => $this->build_order_clause( $args ),
			'limit_clause' => $number > 0 ? (string) $wpdb->prepare( ' LIMIT %d OFFSET %d', $number, $offset ) : '',
		);
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
	 * Build a SQL-safe quoted status list from $args['status'].
	 *
	 * @param array $args Activity args containing a 'status' key.
	 * @return string Comma-separated, single-quoted values, e.g. "'complete','graded','passed','failed'".
	 */
	private function statuses_sql( array $args ): string {
		$raw = (array) ( $args['status'] ?? array() );
		if ( empty( $raw ) ) {
			return "'__none__'";
		}
		// Values originate from class constants or caller-provided filter args,
		// not raw user input.
		$escaped = array();
		foreach ( $raw as $s ) {
			$escaped[] = $this->wpdb->prepare( '%s', (string) $s );
		}
		return implode( ',', $escaped );
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
