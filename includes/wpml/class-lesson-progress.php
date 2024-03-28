<?php
/**
 * File containing \Sensei\WPML\Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Lesson_Progress {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_utils_user_completed_lesson_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_create_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_get_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_has_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_delete_for_lesson_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
		add_filter( 'sensei_lesson_progress_find_lesson_id', array( $this, 'translate_lesson_id' ), 10, 1 );
	}

	/**
	 * Translate course ID.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return int
	 */
	public function translate_lesson_id( $lesson_id ) {
		$details = $this->get_element_language_details( $lesson_id, 'lesson' );

		$original_language_code = $details['source_language_code'] ?? $details['language_code'] ?? null;

		return $this->get_object_id( $lesson_id, 'lesson', true, $original_language_code );
	}
}
