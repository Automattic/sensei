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
 * Class Quiz_Submission
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Quiz_Submission {
	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_quiz_submission_create_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_submission_get_or_create_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
		add_filter( 'sensei_quiz_submission_get_quiz_id', array( $this, 'translate_quiz_id' ), 10, 1 );
	}

	/**
	 * Translate quiz ID.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $quiz Quiz ID.
	 * @return int
	 */
	public function translate_quiz_id( $quiz_id ): int {
		$quiz_id = (int) $quiz_id;
		$details   = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $quiz_id,
				'element_type' => 'quiz',
			)
		);

		$original_language_code = $details['source_language_code'] ?? $details['language_code'] ?? null;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (int) apply_filters( 'wpml_object_id', $quiz_id, 'quiz', true, $original_language_code );
	}
}
