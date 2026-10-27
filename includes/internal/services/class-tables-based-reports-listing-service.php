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
	 * Shared HPPS query mechanics.
	 *
	 * @var Tables_Based_Progress_Query
	 */
	private Tables_Based_Progress_Query $progress_query;

	/**
	 * Constructor.
	 *
	 * @since 4.26.0
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb           = $wpdb;
		$this->progress_query = new Tables_Based_Progress_Query( $wpdb );
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
		$submissions_table = $this->get_quiz_submissions_table_name();
		$post_id           = (int) ( $args['post_id'] ?? 0 );
		$post_id_map       = Utils::get_progress_post_id_map( array( $post_id ), 'lesson' );
		$args['post_id']   = $post_id_map[ $post_id ];

		$selection  = $this->build_selection( $args, 'lesson' );
		$pagination = $this->build_pagination( $selection, $args );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			'SELECT ' . $selection['fields'] . ', qs.final_grade AS grade'
			. $selection['from']
			. " LEFT JOIN `$submissions_table` qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id"
			. $selection['where']
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		Utils::log_query_error( $wpdb, 'Tables-based lesson students items' );

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

		$course_id       = (int) ( $args['post_id'] ?? 0 );
		$post_id_map     = Utils::get_progress_post_id_map( array( $course_id ), 'course' );
		$progress_id     = $post_id_map[ $course_id ];
		$args['post_id'] = $progress_id;
		$selection       = $this->build_selection( $args, 'course' );
		$pagination      = $this->build_pagination( $selection, $args );
		$total_lessons   = count( Sensei()->course->course_lessons( $progress_id, 'publish', 'ids' ) );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each student's course progress with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				// The total_lessons denominator is pre-computed in PHP since all rows share the same course.
				'SELECT ' . $selection['fields'] . ','
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( %d, 0 ), 0 ) AS percent'
				. $selection['from']
				// Derived table: count completed published lessons per student in this course.
				. ' LEFT JOIN ('
				. '   SELECT lp.user_id, COUNT(*) AS cnt'
				. "   FROM `$table` lp"
				. "   INNER JOIN `{$wpdb->postmeta}` pm ON pm.post_id = lp.post_id AND pm.meta_key = '_lesson_course' AND pm.meta_value = %d"
				. "   INNER JOIN `{$wpdb->posts}` lpost ON lpost.ID = lp.post_id AND lpost.post_status = 'publish'"
				. "   WHERE lp.type = 'lesson' AND lp.status = 'complete'"
				. '   GROUP BY lp.user_id'
				. ' ) completed ON completed.user_id = p.user_id',
				$total_lessons,
				$progress_id
			)
			. $selection['where']
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		Utils::log_query_error( $wpdb, 'Tables-based course students items' );

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
		$submissions_table = $this->get_quiz_submissions_table_name();

		$post_id = (int) ( $args['post_id'] ?? 0 );
		$user_id = (int) ( $args['user_id'] ?? 0 );

		$selection = $this->progress_query->build_selection(
			array(
				'type'             => 'lesson',
				'post_id'          => $post_id,
				'user_id'          => $user_id,
				'with_quiz_status' => true,
			)
		);

		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare(). Caching handled by callers.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			'SELECT ' . $selection['fields'] . ', qs.final_grade AS grade'
			. $selection['from']
			. " LEFT JOIN `$submissions_table` qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id"
			. $selection['where']
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		$selection  = $this->build_selection( $args, 'course', true );
		$pagination = $this->build_pagination( $selection, $args );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each course's progress for this user with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				'SELECT ' . $selection['fields'] . ','
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( total.cnt, 0 ), 0 ) AS percent'
				. $selection['from']
				// Derived table: count completed published lessons per course for this user.
				// Lessons are mapped to courses via the _lesson_course postmeta.
				. ' LEFT JOIN ('
				. '   SELECT pm.meta_value AS course_id, COUNT(*) AS cnt'
				. "   FROM `$table` lp"
				. "   INNER JOIN `{$wpdb->postmeta}` pm ON pm.post_id = lp.post_id AND pm.meta_key = '_lesson_course'"
				. "   INNER JOIN `{$wpdb->posts}` lpost ON lpost.ID = lp.post_id AND lpost.post_status = 'publish'"
				. "   WHERE lp.type = 'lesson' AND lp.user_id = %d"
				. "   AND lp.status = 'complete'"
				. '   GROUP BY pm.meta_value'
				. ' ) completed ON completed.course_id = p.post_id'
				// Derived table: count total published lessons per course
				// via the _lesson_course postmeta that maps each lesson to its course.
				. ' LEFT JOIN ('
				. '   SELECT pm.meta_value AS course_id, COUNT(*) AS cnt'
				. "   FROM `{$wpdb->postmeta}` pm"
				. "   INNER JOIN `{$wpdb->posts}` lpost ON lpost.ID = pm.post_id AND lpost.post_status = 'publish'"
				. "   WHERE pm.meta_key = '_lesson_course'"
				. '   GROUP BY pm.meta_value'
				. ' ) total ON total.course_id = p.post_id',
				$user_id
			)
			. $selection['where']
			. $pagination['order_clause']
			. $pagination['limit_clause']
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
	 * Translate Reports activity arguments into storage criteria.
	 *
	 * @param array  $args          Activity arguments.
	 * @param string $type          Progress type.
	 * @param bool   $filter_author Whether to filter by course author.
	 * @return array Prepared progress selection.
	 */
	private function build_selection( array $args, string $type, bool $filter_author = false ): array {
		$criteria = array(
			'type'             => $type,
			'with_quiz_status' => 'lesson' === $type,
			'status_source'    => 'progress',
			'user_exclusions'  => array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES ),
		);

		if ( ! empty( $args['post_id'] ) ) {
			$criteria['post_id'] = (int) $args['post_id'];
		}
		if ( isset( $args['user_id'] ) && '' !== $args['user_id'] && array() !== $args['user_id'] ) {
			$criteria['user_id'] = $args['user_id'];
		}
		if ( ! empty( $args['status'] ) && 'any' !== $args['status'] ) {
			$criteria['status'] = (array) $args['status'];
		}
		if ( ! empty( $args['post_author'] ) && $filter_author ) {
			$criteria['post_author'] = (int) $args['post_author'];
		}

		if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
			foreach ( $args['meta_query'] as $outer ) {
				if ( ! is_array( $outer ) ) {
					continue;
				}
				foreach ( $outer as $clause ) {
					if ( is_array( $clause ) && ( $clause['key'] ?? '' ) === 'start' ) {
						$criteria['started_at'][] = $clause;
					}
				}
			}
		}

		return $this->progress_query->build_selection( $criteria );
	}

	/**
	 * Translate Reports ordering aliases and paginate the selection.
	 *
	 * @param array $selection Prepared progress selection.
	 * @param array $args      Activity arguments.
	 * @return array Count, order, and limit clauses.
	 */
	private function build_pagination( array $selection, array $args ): array {
		$orderby_map = array(
			'comment_date' => 'completed_at',
			'started'      => 'started_at',
			'completed'    => 'completed_at',
		);
		$args['orderby'] = $orderby_map[ $args['orderby'] ?? '' ] ?? 'started_at';

		return $this->progress_query->build_pagination( $selection, $args );
	}
}
