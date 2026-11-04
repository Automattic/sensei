<?php
/**
 * Table-backed student management listings.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads progress for course and lesson student management.
 *
 * @internal
 * @since $$next-version$$
 */
class Tables_Based_Student_Management_Listing_Service implements Student_Management_Listing_Service_Interface {

	/**
	 * Database connection.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Shared progress filters and pagination.
	 *
	 * @var Tables_Based_Progress_Query
	 */
	private Tables_Based_Progress_Query $progress_query;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb Database connection.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb           = $wpdb;
		$this->progress_query = new Tables_Based_Progress_Query( $wpdb );
	}

	/**
	 * Get student activity and its pagination total.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{items: Students_Item[], total_count: int} Activity rows and total.
	 */
	public function get_students( array $args ): array {
		$query = $this->build_query( $args );

		if ( null === $query ) {
			return array(
				'items'       => array(),
				'total_count' => 0,
			);
		}

		$pagination = $this->progress_query->build_pagination( $query['where'], $args, $query['from'] );
		$items      = array();

		if ( $pagination['total_count'] > 0 ) {
			$sql = 'SELECT p.user_id, p.status, p.completed_at, p.updated_at'
				. $query['from'] . $query['where'] . $this->build_order_clause( $args ) . $pagination['limit_clause'];
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query clauses and ordering are prepared by the query helpers.
			$items = (array) $this->wpdb->get_results( $sql );
			Utils::log_query_error( $this->wpdb, 'Student management rows' );
		}

		$students = array();

		foreach ( $items as $row ) {
			// Start-date edits must not replace a completed lesson's completion date with its activity time.
			$date       = 'sensei_lesson_status' === $args['type'] && 'complete' === $row->status && $row->completed_at ? $row->completed_at : $row->updated_at;
			$updated_at = get_date_from_gmt( $date );

			$students[] = new Students_Item(
				(int) $args['post_id'],
				(int) $row->user_id,
				$row->status,
				'complete' === $row->status ? $updated_at : null,
				$updated_at
			);
		}

		return array(
			'items'       => $students,
			'total_count' => $pagination['total_count'],
		);
	}

	/**
	 * Count student activity.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return int Activity count.
	 */
	public function count_students( array $args ): int {
		$query = $this->build_query( $args );

		if ( null === $query ) {
			return 0;
		}

		$pagination = $this->progress_query->build_pagination( $query['where'], array(), $query['from'] );

		return $pagination['total_count'];
	}

	/**
	 * Resolve progress IDs and query filters before counting or paging.
	 *
	 * @param array $args Student activity arguments. post_id is a course or lesson ID;
	 *                    type is sensei_course_status or sensei_lesson_status, respectively.
	 * @return array{from: string, where: string}|null Prepared clauses, or null when no post ID is supplied.
	 */
	private function build_query( array $args ): ?array {
		$wpdb    = $this->wpdb;
		$type    = 'sensei_course_status' === ( $args['type'] ?? '' ) ? 'course' : 'lesson';
		$post_id = (int) ( $args['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return null;
		}

		$id_map          = Utils::get_progress_post_id_map( array( $post_id ), $type );
		$args['post_id'] = $id_map[ $post_id ];
		$table           = $wpdb->prefix . 'sensei_lms_progress';
		$from            = " FROM `$table` p";

		if ( 'lesson' === $type ) {
			// A later forced completion can leave an earlier incomplete quiz row unchanged.
			$forced_completion = "lp.status = 'complete' AND lp.completed_at >= q.updated_at"
				. " AND q.status IN ( 'in-progress', 'failed', 'ungraded' )";
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names use the trusted wpdb prefix; the post ID uses a placeholder.
			$from = $wpdb->prepare(
				' FROM ( SELECT lp.id, lp.post_id, lp.user_id, lp.type, lp.started_at, lp.completed_at,'
				. " CASE WHEN $forced_completion THEN 'passed' ELSE COALESCE( q.status, lp.status ) END AS status,"
				. " CASE WHEN $forced_completion THEN lp.completed_at ELSE COALESCE( q.updated_at, lp.updated_at ) END AS updated_at"
				. " FROM `$table` lp"
				. " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = lp.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0"
				. " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = lp.user_id AND q.type = 'quiz'"
				. " WHERE lp.type = 'lesson' AND lp.post_id = %d ) p",
				$args['post_id']
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$where = $wpdb->prepare( ' WHERE p.type = %s', $type ) . $this->progress_query->build_filters( $args );

		if ( ! isset( $args['user_id'] ) ) {
			$where .= Utils::build_user_exclusion_clause(
				$wpdb,
				array(
					'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES,
					'include_statuses_override'   => array( 'ungraded' ),
				)
			);
		}

		return array(
			'from'  => $from,
			'where' => $where,
		);
	}

	/**
	 * Build student ordering with a stable progress-ID tie-breaker.
	 *
	 * @param array $args Student activity arguments.
	 * @return string SQL ORDER BY clause.
	 */
	private function build_order_clause( array $args ): string {
		$order = 'ASC' === strtoupper( $args['order'] ?? '' ) ? 'ASC' : 'DESC';

		$date = 'sensei_lesson_status' === ( $args['type'] ?? '' )
			? "CASE WHEN p.status = 'complete' THEN COALESCE( p.completed_at, p.updated_at ) ELSE p.updated_at END"
			: 'p.updated_at';

		return " ORDER BY $date $order, p.id $order";
	}
}
