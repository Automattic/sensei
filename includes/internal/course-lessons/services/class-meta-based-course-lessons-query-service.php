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
		add_action( 'pre_get_posts', array( $this, 'filter_lessons_by_course' ) );
	}

	/**
	 * Limit a lesson query to the lessons of the course given in its `sensei_course` query var.
	 *
	 * Added to the query arguments on `pre_get_posts`, so the SQL is the same as with an inline `_lesson_course` meta clause.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	public function filter_lessons_by_course( WP_Query $query ): void {
		$course_id = $query->get( 'sensei_course', null );

		if ( null === $course_id ) {
			return;
		}

		// An empty or invalid course ID still limits the query, to lessons of course 0, as it always has.
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		$meta_query[] = array(
			'key'   => '_lesson_course',
			'value' => (int) $course_id,
		);

		$query->set( 'meta_query', $meta_query );
	}
}
