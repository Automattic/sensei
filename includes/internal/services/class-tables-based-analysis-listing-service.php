<?php
/**
 * File containing the Tables_Based_Analysis_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Tables_Based_Analysis_Listing_Service.
 *
 * Tables-based (HPPS) implementation of the Analysis_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Analysis_Listing_Service implements Analysis_Listing_Service_Interface {

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
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	private function get_progress_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_progress';
	}

	/**
	 * Get the quiz submissions table name.
	 *
	 * @since $$next-version$$
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
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		$lesson_id = (int) $args['lesson_id'];
		$per_page  = (int) ( $args['per_page'] ?? 0 );
		$offset    = (int) ( $args['offset'] ?? 0 );

		$where = $wpdb->prepare( 'WHERE p.post_id = %d AND p.type = %s', $lesson_id, 'lesson' );

		if ( ! empty( $args['search'] ) ) {
			$search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where      .= $wpdb->prepare(
				' AND p.user_id IN ( SELECT ID FROM %i WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s )',
				$wpdb->users,
				$search_like,
				$search_like,
				$search_like
			);
		}

		// Count query.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . " {$where}" );
		Utils::log_query_error( $wpdb, 'Analysis lesson students count' );

		// Snap offset back if beyond total.
		if ( $per_page > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $per_page ) - 1 );
			$offset    = $last_page * $per_page;
		}

		$order_clause = $this->build_lesson_order_clause( $args );
		$limit_clause = $per_page > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset ) : '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where, $order_clause, $limit_clause are built from $wpdb->prepare() or sanitized values.
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
			. " {$where}"
			. " {$order_clause}"
			. " {$limit_clause}"
		);
		Utils::log_query_error( $wpdb, 'Analysis lesson students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Analysis_Item(
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
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$course_id = (int) $args['course_id'];
		$per_page  = (int) ( $args['per_page'] ?? 0 );
		$offset    = (int) ( $args['offset'] ?? 0 );

		$where = $wpdb->prepare( 'WHERE p.post_id = %d AND p.type = %s', $course_id, 'course' );

		if ( ! empty( $args['search'] ) ) {
			$search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where      .= $wpdb->prepare(
				' AND p.user_id IN ( SELECT ID FROM %i WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s )',
				$wpdb->users,
				$search_like,
				$search_like,
				$search_like
			);
		}

		// Apply start date range filter.
		if ( ! empty( $args['start_date_from'] ) ) {
			$where .= $wpdb->prepare( ' AND p.started_at >= %s', $args['start_date_from'] );
		}
		if ( ! empty( $args['start_date_to'] ) ) {
			$where .= $wpdb->prepare( ' AND p.started_at <= %s', $args['start_date_to'] );
		}

		// Count query.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . " {$where}" );
		Utils::log_query_error( $wpdb, 'Analysis course students count' );

		// Snap offset back if beyond total.
		if ( $per_page > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $per_page ) - 1 );
			$offset    = $last_page * $per_page;
		}

		$order_clause = $this->build_course_order_clause( $args );
		$limit_clause = $per_page > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset ) : '';

		// Get total lesson count for percent calculation.
		$total_lessons = $this->get_course_lesson_count( $course_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where, $order_clause, $limit_clause are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE('
				. '   ( SELECT COUNT(*) FROM %i lp'
				. '     LEFT JOIN %i lq ON lq.parent_post_id = lp.post_id AND lq.user_id = lp.user_id AND lq.type = \'quiz\''
				. '     WHERE lp.parent_post_id = p.post_id AND lp.user_id = p.user_id AND lp.type = \'lesson\''
				. '     AND COALESCE( lq.status, lp.status ) IN ( \'complete\', \'graded\', \'passed\', \'failed\', \'ungraded\' )'
				. '   ) * 100.0 / NULLIF( %d, 0 ),'
				. '   0'
				. ' ) AS percent'
				. ' FROM %i p',
				$table,
				$table,
				$total_lessons,
				$table
			)
			. " {$where}"
			. " {$order_clause}"
			. " {$limit_clause}"
		);
		Utils::log_query_error( $wpdb, 'Analysis course students items' );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = new Analysis_Item(
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
	 * Get lesson progress for one user in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   User ID.
	 * @return Analysis_Item[] One item per lesson, keyed by lesson ID. Null values for lessons with no progress.
	 */
	public function get_user_lesson_progress( int $course_id, int $user_id ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		$lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

		if ( empty( $lessons ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching handled by callers.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, COALESCE( q.status, p.status ) AS effective_status,'
				. ' p.started_at, p.completed_at, qs.final_grade AS grade'
				. ' FROM %i p'
				. ' LEFT JOIN %i q ON q.parent_post_id = p.post_id AND q.user_id = p.user_id AND q.type = \'quiz\''
				. ' LEFT JOIN %i qs ON qs.quiz_id = q.post_id AND qs.user_id = p.user_id'
				. ' WHERE p.parent_post_id = %d AND p.user_id = %d AND p.type = \'lesson\'',
				$table,
				$table,
				$submissions_table,
				$course_id,
				$user_id
			)
		);
		Utils::log_query_error( $wpdb, 'Analysis user lesson progress' );

		// Index by post_id for easy lookup.
		$progress_map = array();
		foreach ( $rows as $row ) {
			$progress_map[ (int) $row->post_id ] = $row;
		}

		$items = array();
		foreach ( $lessons as $lesson_id ) {
			if ( ! isset( $progress_map[ $lesson_id ] ) ) {
				$items[ $lesson_id ] = null;
				continue;
			}

			$row                 = $progress_map[ $lesson_id ];
			$items[ $lesson_id ] = new Analysis_Item(
				(int) $row->post_id,
				(int) $row->user_id,
				$row->effective_status,
				$row->started_at ? get_date_from_gmt( $row->started_at ) : null,
				$row->completed_at ? get_date_from_gmt( $row->completed_at ) : null,
				null !== $row->grade ? (float) $row->grade : null,
				null
			);
		}

		return $items;
	}

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		$wpdb  = $this->wpdb;
		$table = $this->get_progress_table_name();

		$user_id  = (int) $args['user_id'];
		$per_page = (int) ( $args['per_page'] ?? 0 );
		$offset   = (int) ( $args['offset'] ?? 0 );

		$where = $wpdb->prepare( 'WHERE p.user_id = %d AND p.type = %s', $user_id, 'course' );

		// Restrict to courses authored by a specific user.
		if ( ! empty( $args['post_author'] ) ) {
			$where .= $wpdb->prepare(
				' AND p.post_id IN ( SELECT ID FROM %i WHERE post_author = %d )',
				$wpdb->posts,
				(int) $args['post_author']
			);
		}

		// Count query.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where is built from $wpdb->prepare() calls.
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i p', $table ) . " {$where}" );
		Utils::log_query_error( $wpdb, 'Analysis user courses count' );

		// Snap offset back if beyond total.
		if ( $per_page > 0 && $total_count > 0 && $offset >= $total_count ) {
			$last_page = max( 0, (int) ceil( $total_count / $per_page ) - 1 );
			$offset    = $last_page * $per_page;
		}

		$order_clause = $this->build_course_order_clause( $args );
		$limit_clause = $per_page > 0 ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset ) : '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where, $order_clause, $limit_clause are built from $wpdb->prepare() or sanitized values.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id, p.user_id, p.status, p.started_at, p.completed_at'
				. ' FROM %i p',
				$table
			)
			. " {$where}"
			. " {$order_clause}"
			. " {$limit_clause}"
		);
		Utils::log_query_error( $wpdb, 'Analysis user courses items' );

		$items = array();
		foreach ( $rows as $row ) {
			$course_id     = (int) $row->post_id;
			$total_lessons = $this->get_course_lesson_count( $course_id );

			$percent = 0;
			if ( $total_lessons > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching handled by callers.
				$completed_lessons = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i lp'
						. ' LEFT JOIN %i lq ON lq.parent_post_id = lp.post_id AND lq.user_id = lp.user_id AND lq.type = \'quiz\''
						. ' WHERE lp.parent_post_id = %d AND lp.user_id = %d AND lp.type = \'lesson\''
						. ' AND COALESCE( lq.status, lp.status ) IN ( \'complete\', \'graded\', \'passed\', \'failed\', \'ungraded\' )',
						$table,
						$table,
						$course_id,
						$user_id
					)
				);
				$percent           = round( $completed_lessons * 100.0 / $total_lessons, 0 );
			}

			$items[] = new Analysis_Item(
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
	 * @return array[] Array of associative arrays with keys: lesson_id, student_count, completion_count, average_grade.
	 */
	public function get_lesson_aggregates( int $course_id ): array {
		$wpdb              = $this->wpdb;
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching handled by callers.
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.post_id AS lesson_id,'
				. ' COUNT( DISTINCT p.user_id ) AS student_count,'
				. ' COUNT( DISTINCT CASE WHEN COALESCE( q.status, p.status ) IN ( \'complete\', \'graded\', \'passed\', \'failed\' ) THEN p.user_id END ) AS completion_count,'
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
		Utils::log_query_error( $wpdb, 'Analysis lesson aggregates' );

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
	 * Get the total number of lessons in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @return int Total lesson count.
	 */
	private function get_course_lesson_count( int $course_id ): int {
		$lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		return count( $lessons );
	}

	/**
	 * Build ORDER BY clause for lesson queries.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_lesson_order_clause( array $args ): string {
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = $args['orderby'] ?? '';

		$orderby_map = array(
			'comment_date' => 'p.completed_at',
			'started'      => 'p.started_at',
			'completed'    => 'p.completed_at',
		);

		$column = esc_sql( $orderby_map[ $orderby ] ?? 'p.started_at' );

		return " ORDER BY {$column} {$order}";
	}

	/**
	 * Build ORDER BY clause for course queries.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Query arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_course_order_clause( array $args ): string {
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = $args['orderby'] ?? '';

		$orderby_map = array(
			'comment_date' => 'p.completed_at',
			'started'      => 'p.started_at',
			'completed'    => 'p.completed_at',
		);

		$column = esc_sql( $orderby_map[ $orderby ] ?? 'p.started_at' );

		return " ORDER BY {$column} {$order}";
	}
}
