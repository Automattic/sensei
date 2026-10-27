<?php
/**
 * File containing the Metas_Based_Course_Lessons_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Metas_Based_Course_Lessons_Repository.
 *
 * Reads the course structure of lessons from post metas: the course a lesson is attached to
 * (`_lesson_course`) and its prerequisite (`_lesson_prerequisite`).
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Metas_Based_Course_Lessons_Repository implements Course_Lessons_Repository_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Metas_Based_Course_Lessons_Repository constructor.
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
}
