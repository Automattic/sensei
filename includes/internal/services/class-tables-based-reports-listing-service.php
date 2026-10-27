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
	 * Shared HPPS query helpers.
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
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();
		$post_id           = (int) ( $args['post_id'] ?? 0 );
		$post_id_map       = Utils::get_progress_post_id_map( array( $post_id ), 'lesson' );
		$args['post_id']   = $post_id_map[ $post_id ];

		$where      = " WHERE p.type = 'lesson'" . $this->progress_query->build_filters( $args )
			. Utils::build_user_exclusion_clause( $wpdb, array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES ) );
		$pagination = $this->progress_query->build_pagination( $where, $args );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			// Fetch each student's lesson progress with their effective quiz/lesson status and grade.
			// Join quiz progress (q) to get the quiz-aware status via COALESCE: if a quiz
			// exists for this lesson, its status takes precedence over the lesson status.
			// Join quiz submissions (qs) to get the final_grade when available.
			'SELECT p.user_id, COALESCE( q.status, p.status ) AS effective_status, p.started_at, p.completed_at, qs.final_grade AS grade'
			. " FROM `$table` p"
			. " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0"
			. " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'"
			. " LEFT JOIN `$submissions_table` qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id"
			. $where
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
		$where           = " WHERE p.type = 'course'" . $this->progress_query->build_filters( $args )
			. Utils::build_user_exclusion_clause( $wpdb, array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES ) );
		$pagination      = $this->progress_query->build_pagination( $where, $args );
		$total_lessons   = count( Sensei()->course->course_lessons( $progress_id, 'publish', 'ids' ) );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each student's course progress with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				// The total_lessons denominator is pre-computed in PHP since all rows share the same course.
				'SELECT p.user_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( %d, 0 ), 0 ) AS percent'
				. " FROM `$table` p"
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
			. $where
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
		$table             = $this->get_progress_table_name();
		$submissions_table = $this->get_quiz_submissions_table_name();

		$post_id = (int) ( $args['post_id'] ?? 0 );
		$user_id = (int) ( $args['user_id'] ?? 0 );

		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare(). Caching handled by callers.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// Fetch one lesson's progress for one user, with quiz-aware effective status.
				// Same JOIN pattern as get_lesson_students but for a single row.
				'SELECT COALESCE( q.status, p.status ) AS effective_status,'
				. ' p.started_at, p.completed_at, qs.final_grade AS grade'
				. " FROM `$table` p"
				. " LEFT JOIN `{$wpdb->postmeta}` pm ON pm.post_id = p.post_id AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0"
				. " LEFT JOIN `$table` q ON q.post_id = pm.meta_value AND q.user_id = p.user_id AND q.type = 'quiz'"
				. " LEFT JOIN `$submissions_table` qs ON qs.quiz_id = pm.meta_value AND qs.user_id = p.user_id"
				. " WHERE p.post_id = %d AND p.user_id = %d AND p.type = 'lesson'",
				$post_id,
				$user_id
			)
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

		$where = " WHERE p.type = 'course'" . $this->progress_query->build_filters( $args )
			. Utils::build_user_exclusion_clause( $wpdb, array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES ) );

		if ( ! empty( $args['post_author'] ) ) {
			// Table name is a trusted $wpdb property; value clause uses $wpdb->prepare().
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$where .= $wpdb->prepare(
				" AND p.post_id IN ( SELECT ID FROM `{$wpdb->posts}` WHERE post_author = %d )",
				(int) $args['post_author']
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$pagination = $this->progress_query->build_pagination( $where, $args );

		/** Query result rows. @var object[] $rows */
		// Table names are trusted $wpdb properties; value clauses use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				// Fetch each course's progress for this user with a computed percent column.
				// percent = (completed lessons / total lessons) * 100.
				'SELECT p.post_id, p.status, p.started_at, p.completed_at,'
				. ' COALESCE( completed.cnt * 100.0 / NULLIF( total.cnt, 0 ), 0 ) AS percent'
				. " FROM `$table` p"
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
			. $where
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

}
