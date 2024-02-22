<?php
/**
 * File containing \Sensei\WPML\Quiz_Progress class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Quiz_Progress
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Quiz_Progress {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_quiz_progress_create_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_progress_get_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_progress_has_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_progress_delete_for_quiz_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_progress_find_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
	}

	/**
	 * Translate quiz ID.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz ID.
	 * @return int
	 */
	public function translate_quiz_id( $quiz_id ) {
		$details = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $quiz_id,
				'element_type' => 'quiz',
			)
		);

		$original_language_code = $details['source_language_code'] ?? null;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (int) apply_filters( 'wpml_object_id', $quiz_id, 'quiz', true, $original_language_code );
	}
}
