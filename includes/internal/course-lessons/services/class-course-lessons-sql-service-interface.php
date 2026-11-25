<?php
/**
 * File containing the Course_Lessons_SQL_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Course_Lessons_SQL_Service_Interface.
 *
 * Provides SQL for the course structure, so queries do not depend on where it is stored.
 *
 * @internal
 *
 * @since 4.28.0
 */
interface Course_Lessons_SQL_Service_Interface {
	/**
	 * Get the SQL clauses a query uses to read the course of each lesson.
	 *
	 * The query must include `where`. A lesson without a course can have an empty or 0 course.
	 *
	 * @internal
	 *
	 * @param string $alias Alias the query gives to the table.
	 * @return array{table: string, lesson_id: string, course_id: string, where: string} Table name, column expressions and condition.
	 */
	public function get_lesson_course_clauses( string $alias ): array;
}
