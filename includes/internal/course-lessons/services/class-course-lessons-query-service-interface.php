<?php
/**
 * File containing the Course_Lessons_Query_Service_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Course_Lessons_Query_Service_Interface.
 *
 * Resolves the course structure conditions a lesson WP_Query asks for in domain terms,
 * so the query does not depend on where the structure is stored.
 *
 * @internal
 *
 * @since $$next-version$$
 */
interface Course_Lessons_Query_Service_Interface {
	/**
	 * Limit a lesson query to the lessons of the course given in its `sensei_course` query var.
	 *
	 * @internal
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function filter_lessons_by_course( \WP_Query $query ): void;
}
