<?php
/**
 * File containing \Sensei\WPML\Course_Progress class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Course_Progress {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_course_is_user_enrolled_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_block_take_course_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_course_progress_create_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_course_progress_get_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_course_progress_has_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_course_progress_delete_for_course_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_course_progress_find_course_id', array( $this, 'translate_course_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_count_course_id', array( $this, 'translate_course_id' ), 10, 1 );
	}

	/**
	 * Translate course ID.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $course_id Course ID.
	 * @return int
	 */
	public function translate_course_id( $course_id ): int {
		$course_id = (int) $course_id;
		$details   = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $course_id,
				'element_type' => 'course',
			)
		);

		$original_language_code = $details['source_language_code'] ?? null;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (int) apply_filters( 'wpml_object_id', $course_id, 'course', true, $original_language_code );
	}
}
