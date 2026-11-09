<?php
/**
 * File containing the Meta_Based_Course_Lessons_Query_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Based_Course_Lessons_Query_Service.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Meta_Based_Course_Lessons_Query_Service implements Course_Lessons_Query_Service_Interface {
	/**
	 * Register the hooks that resolve the query conditions.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function init(): void {
		// Runs at a late priority so it sees the final `sensei_course` after other
		// `pre_get_posts` callbacks (e.g. the module archive) have set it.
		add_action( 'pre_get_posts', array( $this, 'filter_lessons_by_course' ), 999 );
	}

	/**
	 * Limit a lesson query to the lessons of the course given in its `sensei_course` query var.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	public function filter_lessons_by_course( WP_Query $query ): void {
		$course_id = $query->get( 'sensei_course', null );

		// Only null skips the course limit; any other value, even 0, is cast and limits the query.
		if ( null === $course_id ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		$course_clause = array(
			'key'   => '_lesson_course',
			'value' => (int) $course_id,
		);

		// The course is required even when the query's own clauses are joined with OR. It goes
		// last, so the query's own first clause stays the one `orderby => meta_value` uses.
		$query->set( 'meta_query', array( $meta_query, $course_clause ) );
	}
}
