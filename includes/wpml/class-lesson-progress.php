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
 * @since 4.23.1
 *
 * @internal
 */
class Lesson_Progress {
	use Progress_Query_Helper;
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_utils_user_completed_lesson_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_lesson_progress_create_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_lesson_progress_get_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_lesson_progress_has_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_lesson_progress_delete_for_lesson_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_lesson_progress_find_lesson_id', array( $this, 'translate_lesson_id' ) );
		add_filter( 'sensei_quiz_cache_key_lesson_id', array( $this, 'translate_lesson_id' ) );

		// The student management screens query lesson progress with the ID of the lesson they show.
		add_filter( 'sensei_check_for_activity_args', array( $this, 'translate_lesson_query_args' ) );
		// The grading screen queries it through the progress query services.
		add_filter( 'sensei_grading_filter_statuses', array( $this, 'translate_lesson_query_args' ) );
		// A teacher's own lessons come from the current language, and the grading
		// screen intersects them with the query above, so they need the same IDs.
		add_filter( 'sensei_count_statuses_args', array( $this, 'translate_lesson_query_args' ), 20 );
	}

	/**
	 * Point the lesson IDs of a progress query at the original language.
	 *
	 * Progress is stored against the original language, but the admin screens
	 * query it with the ID of the lesson they are showing, which in a secondary
	 * language is the translation, so the query finds nothing.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param mixed $args Query arguments.
	 * @return mixed
	 */
	public function translate_lesson_query_args( $args ) {
		return $this->translate_query_post_ids( $args, 'lesson', array( $this, 'translate_lesson_id' ) );
	}

	/**
	 * Translate lesson ID.
	 *
	 * @since 4.23.1
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
