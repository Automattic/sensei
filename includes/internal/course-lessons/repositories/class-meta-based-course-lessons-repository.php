<?php
/**
 * File containing the Meta_Based_Course_Lessons_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

use wpdb;
use WP_Error;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Based_Course_Lessons_Repository.
 *
 * @internal
 *
 * @since 4.27.0
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
	 * @since 4.27.0
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
	 * @since 4.27.0
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
	 * @since 4.27.0
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
	 * @since 4.27.0
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
	 * @since 4.27.0
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
	 * @since 4.27.0
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

	/**
	 * Set the prerequisite of a lesson.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id              Lesson ID.
	 * @param int $prerequisite_lesson_id Prerequisite lesson ID.
	 */
	public function set_lesson_prerequisite( int $lesson_id, int $prerequisite_lesson_id ): void {
		update_post_meta( $lesson_id, '_lesson_prerequisite', $prerequisite_lesson_id );
	}

	/**
	 * Delete the prerequisite of a lesson.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_lesson_prerequisite( int $lesson_id ): void {
		delete_post_meta( $lesson_id, '_lesson_prerequisite' );
	}

	/**
	 * Get the IDs of the lessons that have the given lesson as their prerequisite.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $prerequisite_lesson_id Prerequisite lesson ID.
	 * @return int[] Lesson IDs, in no particular order. Trashed lessons are left out.
	 */
	public function get_lesson_ids_with_prerequisite( int $prerequisite_lesson_id ): array {
		$lesson_ids = get_posts(
			array(
				'post_type'        => 'lesson',
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'none',
				'suppress_filters' => true,
				'meta_key'         => '_lesson_prerequisite', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Prerequisites are stored in a meta.
				'meta_value'       => $prerequisite_lesson_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
			)
		);

		return array_map( 'intval', $lesson_ids );
	}

	/**
	 * Set the order of a lesson in a course.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @param int $order     Order.
	 */
	public function set_lesson_order( int $lesson_id, int $course_id, int $order ): void {
		update_post_meta( $lesson_id, '_order_' . $course_id, $order );
	}

	/**
	 * Delete the order of a lesson in a course.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 */
	public function delete_lesson_order( int $lesson_id, int $course_id ): void {
		delete_post_meta( $lesson_id, '_order_' . $course_id );
	}

	/**
	 * Set the order of the lessons that are not in a module of a course.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int   $course_id  Course ID.
	 * @param int[] $lesson_ids Lesson IDs, in order.
	 */
	public function set_root_lesson_order( int $course_id, array $lesson_ids ): void {
		update_post_meta( $course_id, '_lesson_order', implode( ',', $lesson_ids ) );
	}

	/**
	 * Get the order of the lessons that are not in a module of a course.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $course_id Course ID.
	 * @return int[] Lesson IDs, in order. Empty when none is stored.
	 */
	public function get_root_lesson_order( int $course_id ): array {
		$lesson_order = get_post_meta( $course_id, '_lesson_order', true );
		if ( empty( $lesson_order ) ) {
			return array();
		}

		return array_map( 'intval', explode( ',', $lesson_order ) );
	}

	/**
	 * Whether a lesson is in the given module.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @return bool
	 */
	public function has_lesson_module( int $lesson_id, int $term_id ): bool {
		return has_term( $term_id, 'module', $lesson_id );
	}

	/**
	 * Set the module of a lesson, replacing any module it had.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @return true|WP_Error True, or the error `wp_set_object_terms()` returned.
	 */
	public function set_lesson_module( int $lesson_id, int $term_id ) {
		$result = wp_set_object_terms( $lesson_id, array( $term_id ), 'module' );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Remove a lesson from its module through `wp_delete_object_term_relationships()`.
	 *
	 * Unlike `unset_lesson_module()`, the `set_object_terms` action does not fire.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_lesson_module( int $lesson_id ): void {
		wp_delete_object_term_relationships( $lesson_id, 'module' );
	}

	/**
	 * Remove a lesson from its module through `wp_set_object_terms()` with no terms.
	 *
	 * Same result as `delete_lesson_module()`, but the `set_object_terms` action fires as well, with
	 * the old terms as term taxonomy IDs. Both fire the term relationship deletion hooks.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function unset_lesson_module( int $lesson_id ): void {
		wp_set_object_terms( $lesson_id, array(), 'module' );
	}

	/**
	 * Get the order of a lesson in a module.
	 *
	 * Non-canonical values such as `'3abc'` are read as 3, like `intval()`.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @return int|null Order, or null when the meta is missing or empty.
	 */
	public function get_lesson_module_order( int $lesson_id, int $term_id ): ?int {
		$order = get_post_meta( $lesson_id, '_order_module_' . $term_id, true );

		return '' === $order ? null : (int) $order;
	}

	/**
	 * Set the order of a lesson in a module.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 * @param int $order     Order.
	 */
	public function set_lesson_module_order( int $lesson_id, int $term_id, int $order ): void {
		update_post_meta( $lesson_id, '_order_module_' . $term_id, $order );
	}

	/**
	 * Delete the order of a lesson in a module.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $term_id   Module term ID.
	 */
	public function delete_lesson_module_order( int $lesson_id, int $term_id ): void {
		delete_post_meta( $lesson_id, '_order_module_' . $term_id );
	}

	/**
	 * Delete the order of a lesson in each module it is in.
	 *
	 * Reads the modules with `get_the_terms()`, so the term cache and its filters apply.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function delete_all_lesson_module_orders( int $lesson_id ): void {
		$modules = get_the_terms( $lesson_id, 'module' );

		if ( ! is_array( $modules ) ) {
			return;
		}

		foreach ( $modules as $module ) {
			delete_post_meta( $lesson_id, '_order_module_' . $module->term_id );
		}
	}

	/**
	 * Attach a lesson to a course, replacing the course it had.
	 *
	 * Writes `_lesson_course` only; orders, module and prerequisite are left as they are.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 */
	public function attach_lesson( int $lesson_id, int $course_id ): void {
		update_post_meta( $lesson_id, '_lesson_course', $course_id );
	}

	/**
	 * Detach a lesson from its course.
	 *
	 * Deletes `_lesson_course` only; orders, module and prerequisite are left as they are.
	 *
	 * @internal
	 *
	 * @since 4.27.0
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function detach_lesson( int $lesson_id ): void {
		delete_post_meta( $lesson_id, '_lesson_course' );
	}
}
