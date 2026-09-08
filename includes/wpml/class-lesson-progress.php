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

		add_filter( 'sensei_check_for_activity_args', array( $this, 'translate_lesson_query_args' ) );
		add_filter( 'sensei_grading_filter_statuses', array( $this, 'translate_lesson_query_args' ) );
		// Teachers only see their own lessons, and that list is built in the current
		// language before this runs. It needs translating like the rest of the query.
		add_filter( 'sensei_count_statuses_args', array( $this, 'translate_lesson_query_args' ), 20 );
	}

	/**
	 * Translate the lesson IDs of a progress query to the original language.
	 *
	 * A lesson and its translations share one progress, stored against the
	 * original language's ID. The admin screens query it with the ID of the
	 * lesson they are showing, which in a secondary language is a translation,
	 * so the query would find nothing. Translating the ID first makes the same
	 * query hit the stored progress whatever the admin language is.
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
