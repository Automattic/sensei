<?php
/**
 * File containing the \Sensei\WPML\Quiz_Translation_Helper trait.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Quiz_Translation_Helper
 *
 * @since 4.22.0
 *
 * @internal
 */
trait Quiz_Translation_Helper {

	/**
	 * Update quiz translations.
	 *
	 * @param int $master_lesson_id Master lesson ID.
	 */
	private function update_quiz_translations( $master_lesson_id ) {
		$master_quiz_id = Sensei()->lesson->lesson_quizzes( $master_lesson_id, 'any', 'ids' );
		if ( empty( $master_quiz_id ) ) {
			return;
		}

		// Create translations for questions if they don't exist.
		$questions = Sensei()->quiz->get_questions( $master_quiz_id );
		foreach ( $questions as $question ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$is_question_translated = apply_filters( 'wpml_element_has_translations', '', $question->ID, 'question' );
			if ( ! $is_question_translated ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wpml_admin_make_post_duplicates', $question->ID );
			}
		}

		// Create translations for the quiz if they don't exist.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$is_quiz_translated = apply_filters( 'wpml_element_has_translations', '', $master_quiz_id, 'quiz' );
		if ( ! $is_quiz_translated ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'wpml_admin_make_post_duplicates', $master_quiz_id );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$quiz_translations = apply_filters( 'wpml_post_duplicates', $master_quiz_id );
		foreach ( $quiz_translations as $translation_lang => $translated_quiz_id ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$quiz_lesson_id = apply_filters( 'wpml_object_id', $master_lesson_id, 'lesson', false, $translation_lang );

			// Update _quiz_lesson and _lesson_quiz field.
			update_post_meta( $translated_quiz_id, '_quiz_lesson', $quiz_lesson_id );
			update_post_meta( $quiz_lesson_id, '_lesson_quiz', $translated_quiz_id );

			// Add relationship between quiz and questions.
			if ( ! empty( $questions ) ) {
				foreach ( $questions as $question ) {

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					$translated_question_id = apply_filters( 'wpml_object_id', $question->ID, 'question', false, $translation_lang );
					if ( empty( $translated_question_id ) ) {
						continue;
					}

					update_post_meta( $translated_question_id, '_quiz_id', $translated_quiz_id );

					$question_order = get_post_meta( $question->ID, '_quiz_question_order' . $master_quiz_id, true );
					update_post_meta( $translated_question_id, '_quiz_question_order' . $translated_quiz_id, $question_order );
				}
			}
		}
	}
}
