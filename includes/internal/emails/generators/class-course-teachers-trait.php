<?php
/**
 * File containing the Course_Teachers_Trait class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Course_Teachers_Trait.
 *
 * @internal
 *
 * @since 4.15.1
 */
trait Course_Teachers_Trait {
	/**
	 * Get the teacher IDs for a given course.
	 *
	 * @internal
	 *
	 * @since 4.15.1
	 *
	 * @param int $course_id The course ID.
	 * @return array The teacher IDs.
	 */
	public function get_course_teachers( $course_id ): array {
		$teacher_id = get_post_field( 'post_author', $course_id, 'raw' );

		/**
		 * Filter the teacher IDs for a given course.
		 *
		 * @since 4.15.1
		 *
		 * @hook sensei_email_course_teachers
		 *
		 * @param {array} $teacher_ids The teacher IDs.
		 * @param {int}   $course_id   The course ID.
		 * @return {array} The teacher IDs.
		 */
		return apply_filters( 'sensei_email_course_teachers', array( $teacher_id ), $course_id );
	}
}
