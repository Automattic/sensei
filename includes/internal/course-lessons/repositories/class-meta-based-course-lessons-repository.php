<?php
/**
 * File containing the Meta_Based_Course_Lessons_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

use wpdb;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Based_Course_Lessons_Repository.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Meta_Based_Course_Lessons_Repository implements Course_Lessons_Repository_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Meta_Based_Course_Lessons_Repository constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the course a lesson is attached to.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Course ID, or null when the lesson has no course.
	 */
	public function get_course_id( int $lesson_id ): ?int {
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );

		return is_numeric( $course_id ) && (int) $course_id > 0 ? (int) $course_id : null;
	}

	/**
	 * Get the prerequisite lesson ID stored on a lesson.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int|null Prerequisite lesson ID, or null when none is stored.
	 */
	public function get_lesson_prerequisite_id( int $lesson_id ): ?int {
		$prerequisite_lesson_id = get_post_meta( $lesson_id, '_lesson_prerequisite', true );

		return is_numeric( $prerequisite_lesson_id ) && (int) $prerequisite_lesson_id > 0 ? (int) $prerequisite_lesson_id : null;
	}

	/**
	 * Get the course of each of the given lessons.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param array $lesson_ids Lesson IDs.
	 * @return array<int, int|false> Course ID keyed by lesson ID, or false when the lesson has no existing course.
	 */
	public function get_course_ids( array $lesson_ids ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		sort( $lesson_ids, SORT_NUMERIC );
		$lesson_ids = array_unique( $lesson_ids, SORT_NUMERIC );

		$cache_key     = 'lesson/get-course-ids/' . md5( implode( ',', $lesson_ids ) );
		$cache_group   = 'sensei/temporary';
		$cached_result = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached_result ) {
			return $cached_result;
		}
		$courses_by_lesson = array_fill_keys( $lesson_ids, false );
		$placeholders      = implode( ', ', array_fill( 0, count( $lesson_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$query = $this->wpdb->prepare(
			"
				SELECT lesson.ID AS lesson_id, course.ID AS course_id FROM {$this->wpdb->posts} lesson
					INNER JOIN {$this->wpdb->postmeta} AS lesson_meta ON lesson_meta.post_id=lesson.ID AND lesson_meta.meta_key='_lesson_course'
					INNER JOIN {$this->wpdb->posts} AS course ON course.ID = lesson_meta.meta_value AND course.post_type='course'
					WHERE lesson.ID in ({$placeholders})
				  		AND lesson.post_type='lesson'
		",
			$lesson_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $query );
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$courses_by_lesson[ (int) $result->lesson_id ] = (int) $result->course_id;
			}
			wp_cache_set( $cache_key, $courses_by_lesson, $cache_group, 60 );
		}
		return $courses_by_lesson;
	}

	/**
	 * Get the IDs of the lessons attached to a course.
	 *
	 * `suppress_filters` keeps multilingual plugins from limiting the query to the current language.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int             $course_id   Course ID.
	 * @param string|string[] $post_status Lesson post status or statuses. Default 'publish'.
	 * @return int[] Lesson IDs, in no particular order.
	 */
	public function get_lesson_ids( int $course_id, $post_status = 'publish' ): array {
		$lesson_ids = get_posts(
			array(
				'post_type'        => 'lesson',
				'post_status'      => $post_status,
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'none',
				'suppress_filters' => true,
				'meta_key'         => '_lesson_course', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Lessons are attached to their course by meta.
				'meta_value'       => $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
			)
		);

		return array_map( 'intval', $lesson_ids );
	}

	/**
	 * Get the order of a lesson in a course.
	 *
	 * Non-canonical values such as `'3abc'` are read as 3, like `intval()`.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @return int|null Order, or null when the meta is missing or empty.
	 */
	public function get_lesson_order( int $lesson_id, int $course_id ): ?int {
		$order = get_post_meta( $lesson_id, '_order_' . $course_id, true );

		return '' === $order ? null : (int) $order;
	}

	/**
	 * Get the module of a lesson.
	 *
	 * Reads with `wp_get_post_terms()`, so the `get_object_terms` filters apply.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return WP_Term|null The lesson's module term, or null when it has none or a filter returns no term.
	 */
	public function get_lesson_module( int $lesson_id ): ?WP_Term {
		$terms = wp_get_post_terms( $lesson_id, 'module' );

		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return null;
		}

		/**
		 * A get_object_terms filter can hand back something other than a term.
		 *
		 * @var mixed $term
		 */
		$term = reset( $terms );

		return $term instanceof WP_Term ? $term : null;
	}
}
