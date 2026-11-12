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
 * @since 4.27.0
 */
class Meta_Based_Course_Lessons_Query_Service implements Course_Lessons_Query_Service_Interface {
	/**
	 * Register the hooks that resolve the query conditions.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 */
	public function init(): void {
		// Runs at a late priority so it sees the final query vars after other
		// `pre_get_posts` callbacks (e.g. the module archive) have set them.
		add_action( 'pre_get_posts', array( $this, 'filter_lessons_by_course' ), 999 );
		add_action( 'pre_get_posts', array( $this, 'order_lessons_in_course' ), 999 );
		add_action( 'pre_get_posts', array( $this, 'order_lessons_in_module' ), 999 );
	}

	/**
	 * Limit a lesson query to the lessons of the course given in its `sensei_course` query var.
	 *
	 * @internal
	 *
	 * @since 4.27.0
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

	/**
	 * Sort the lessons of a query by their order in the course set in the `sensei_lesson_order_course` query var.
	 *
	 * Lessons without an order in the course are not returned.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	public function order_lessons_in_course( WP_Query $query ): void {
		$course_id = $query->get( 'sensei_lesson_order_course', null );

		if ( null === $course_id ) {
			return;
		}

		$this->order_lessons_by_meta_key( $query, '_order_' . (int) $course_id );
	}

	/**
	 * Sort the lessons of a query by their order in the module set in the `sensei_lesson_order_module` query var.
	 *
	 * Lessons without an order in the module are not returned.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	public function order_lessons_in_module( WP_Query $query ): void {
		$module_id = $query->get( 'sensei_lesson_order_module', null );

		if ( null === $module_id ) {
			return;
		}

		$this->order_lessons_by_meta_key( $query, '_order_module_' . (int) $module_id );
	}

	/**
	 * Order a lesson query by the lesson order stored in a meta key.
	 *
	 * @param WP_Query $query    The query being prepared.
	 * @param string   $meta_key The meta key that stores the order of a lesson.
	 */
	private function order_lessons_by_meta_key( WP_Query $query, string $meta_key ): void {
		// Keep the query's own `meta_key`.
		if ( '' !== $query->get( 'meta_key' ) ) {
			return;
		}

		$query->set( 'meta_key', $meta_key );

		// An array `orderby` keeps its order; the lessons without an order are still left out.
		$orderby = $query->get( 'orderby' );
		if ( is_string( $orderby ) ) {
			$query->set( 'orderby', 'meta_value_num ' . $orderby );
		}
	}
}
