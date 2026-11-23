<?php
/**
 * File containing the Meta_Based_Course_Lessons_SQL_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Based_Course_Lessons_SQL_Service.
 *
 * @internal
 *
 * @since 4.28.0
 */
class Meta_Based_Course_Lessons_SQL_Service implements Course_Lessons_SQL_Service_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Meta_Based_Course_Lessons_SQL_Service constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get the SQL clauses a query uses to read the course of each lesson.
	 *
	 * The query must include `where`. A lesson without a course can have an empty or 0 course.
	 *
	 * @internal
	 *
	 * @since 4.28.0
	 *
	 * @param string $alias Alias the query gives to the table.
	 * @return array{table: string, lesson_id: string, course_id: string, where: string} Table name, column expressions and condition.
	 */
	public function get_lesson_course_clauses( string $alias ): array {
		return array(
			'table'     => $this->wpdb->postmeta,
			'lesson_id' => "{$alias}.post_id",
			'course_id' => "{$alias}.meta_value",
			'where'     => "{$alias}.meta_key = '_lesson_course'",
		);
	}
}
