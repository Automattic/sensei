<?php
/**
 * File containing \Sensei\WPML\Question_Submission class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Question_Submission
 *
 * Compatibility code with WPML.
 *
 * @since $$next-version$$
 *
 * @internal
 */
class Question_Submission {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		add_filter( 'sensei_quiz_answer_create_question_id', array( $this, 'translate_question_id' ) );
		add_filter( 'sensei_quiz_grade_create_question_id', array( $this, 'translate_question_id' ) );
		add_filter( 'sensei_quiz_grade_save_many_question_id', array( $this, 'translate_question_id' ) );
	}

	/**
	 * Translate question ID to the original language.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int $question_id Question ID.
	 * @return int
	 */
	public function translate_question_id( $question_id ) {
		$details = $this->get_element_language_details( $question_id, 'question' );

		$original_language_code = $details['source_language_code'] ?? $details['language_code'] ?? null;

		return $this->get_object_id( $question_id, 'question', true, $original_language_code );
	}
}
