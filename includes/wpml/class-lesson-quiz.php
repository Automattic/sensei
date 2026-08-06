<?php
/**
 * File containing the Lesson_Quiz class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Quiz
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Lesson_Quiz {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_lesson_quiz_fallback', array( $this, 'resolve_quiz_from_meta' ), 10, 4 );
	}

	/**
	 * Resolve a lesson's quiz from the `_lesson_quiz` lesson meta.
	 *
	 * WPML filters post queries by language, so the query by `post_parent` can miss the
	 * lesson's quiz: with the admin in a secondary language, or on a translated lesson
	 * whose quiz is untranslated (the quiz's parent is the original lesson, so no query
	 * can find it from the translation). The lesson meta bridges both cases.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int|\WP_Post|null $quiz_id     The quiz resolved so far, or null.
	 * @param int               $lesson_id   Lesson ID.
	 * @param string|string[]   $post_status The requested post status.
	 * @param string            $fields      The requested fields format.
	 * @return int|\WP_Post|null The quiz matching the requested status and fields, or null.
	 */
	public function resolve_quiz_from_meta( $quiz_id, $lesson_id, $post_status, $fields ) {
		if ( $quiz_id ) {
			return $quiz_id;
		}

		$meta_quiz_id = (int) get_post_meta( $lesson_id, '_lesson_quiz', true );

		if ( ! $meta_quiz_id ) {
			return null;
		}

		// The quiz is looked up by ID instead of returned straight from the meta because the
		// caller can ask for specific post statuses and for a different fields format, and the
		// query is what applies them. Filters are suppressed here: they are what hid the quiz
		// from the query this filter rescues.
		$posts_array = get_posts(
			array(
				'p'                => $meta_quiz_id,
				'post_type'        => 'quiz',
				'posts_per_page'   => 1,
				'post_status'      => $post_status,
				'fields'           => $fields,
				'suppress_filters' => true,
			)
		);

		return array_shift( $posts_array );
	}
}
