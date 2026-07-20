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
	use WPML_API;

	/**
	 * Update quiz translations.
	 *
	 * @param int    $master_lesson_id Master lesson ID.
	 * @param string $target_language  Target language.
	 */
	private function update_quiz_translations( $master_lesson_id, $target_language ) {
		$master_quiz_id = Sensei()->lesson->lesson_quizzes( $master_lesson_id, 'any', 'ids' );
		if ( empty( $master_quiz_id ) ) {
			return;
		}

		$questions       = Sensei()->quiz->get_questions( $master_quiz_id );
		$question_id_map = array();
		foreach ( $questions as $question ) {
			$has_translation                  = $this->has_translation_in_language( $question->ID, 'post_' . $question->post_type, $target_language );
			$question_id_map[ $question->ID ] = $has_translation
				? $this->get_object_id( $question->ID, $question->post_type, false, $target_language )
				: $this->copy_post_to_language( $question->ID, $target_language, true );
		}

		// Create translations for the quiz if they don't exist.
		$is_quiz_translated = $this->has_translation_in_language( $master_quiz_id, 'post_quiz', $target_language );
		$translated_quiz_id = $is_quiz_translated
			? $this->get_object_id( $master_quiz_id, 'quiz', false, $target_language )
			: $this->copy_post_to_language( $master_quiz_id, $target_language, true );

		if ( empty( $translated_quiz_id ) ) {
			return;
		}

		$quiz_lesson_id = $this->get_object_id( $master_lesson_id, 'lesson', false, $target_language );

		// Update _quiz_lesson and _lesson_quiz field.
		update_post_meta( $translated_quiz_id, '_quiz_lesson', $quiz_lesson_id );
		update_post_meta( $quiz_lesson_id, '_lesson_quiz', $translated_quiz_id );

		// Sync the quiz title and slug from the translated lesson. The copy above
		// carries the master lesson's title, and Sensei_Quiz::update_after_lesson_change
		// cannot repair it in translation contexts because its quiz lookup is
		// filtered to the current language.
		$quiz_lesson = get_post( $quiz_lesson_id );
		if ( $quiz_lesson ) {
			wp_update_post(
				array(
					'ID'         => $translated_quiz_id,
					'post_title' => $quiz_lesson->post_title,
					'post_name'  => $quiz_lesson->post_name,
				)
			);
		}

		update_post_meta( $quiz_lesson_id, '_quiz_has_questions', count( $questions ) > 0 );

		// Add relationship between quiz and questions.
		if ( empty( $questions ) ) {
			return;
		}

		foreach ( $questions as $question ) {
			$translated_question_id = $question_id_map[ $question->ID ] ?? null;
			if ( empty( $translated_question_id ) ) {
				continue;
			}

			if ( 'multiple_question' === $question->post_type ) {
				$category = (int) get_post_meta( $question->ID, 'category', true );
				$number   = (int) get_post_meta( $question->ID, 'number', true );

				$this->create_translation_for_question_category( $category, $target_language );

				$translated_category_id = $this->get_object_id( $category, 'question-category', false, $target_language );

				update_post_meta( $translated_question_id, 'category', $translated_category_id );
				update_post_meta( $translated_question_id, 'number', $number );

				$this->create_translations_for_question_category_questions( $category, $target_language );
			}

			update_post_meta( $translated_question_id, '_quiz_id', $translated_quiz_id );

			$question_order = get_post_meta( $question->ID, '_quiz_question_order' . $master_quiz_id, true );
			update_post_meta( $translated_question_id, '_quiz_question_order' . $translated_quiz_id, $question_order );
		}
	}

	/**
	 * Create translation for the question category.
	 *
	 * Normally, we don't need to create translations for the question category.
	 *
	 * @param int    $original_category_id Original category ID.
	 * @param string $translation_lang     Translation language.
	 */
	private function create_translation_for_question_category( $original_category_id, $translation_lang ) {
		if ( $this->has_translation_in_language( $original_category_id, 'tax_question-category', $translation_lang ) ) {
			return;
		}

		$details = $this->get_element_language_details( $original_category_id, 'question-category' );

		$trid = $this->get_element_trid( $original_category_id, 'tax_question-category' );
		if ( ! $trid ) {
			$args = array(
				'element_id'           => $original_category_id,
				'element_type'         => 'tax_question-category',
				'trid'                 => false,
				'language_code'        => $details['language_code'],
				'source_language_code' => null,
			);
			$this->set_element_language_details( $args );
		}

		$trid = $this->get_element_trid( $original_category_id, 'tax_question-category' );
		if ( ! $trid ) {
			return;
		}

		$existing_term = get_term( $original_category_id, 'question-category' );
		if ( ! $existing_term ) {
			return;
		}

		$args = array(
			'slug'        => $existing_term->slug . '-' . $translation_lang,
			'description' => $existing_term->description,
		);

		$new_term = wp_insert_term( $existing_term->name, 'question-category', $args );
		if ( is_wp_error( $new_term ) ) {
			return;
		}

		$args = array(
			'element_id'           => $new_term['term_id'],
			'element_type'         => 'tax_question-category',
			'trid'                 => $trid,
			'language_code'        => $translation_lang,
			'source_language_code' => $details['language_code'],
		);
		$this->set_element_language_details( $args );
	}

	/**
	 * Create translations for questions in the category.
	 *
	 * @param int    $question_category_id Question category ID.
	 * @param string $target_language Target language.
	 */
	private function create_translations_for_question_category_questions( $question_category_id, $target_language ) {
		$args = array(
			'post_type'        => 'question',
			'posts_per_page'   => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'        => array(
				array(
					'taxonomy' => 'question-category',
					'field'    => 'term_id',
					'terms'    => $question_category_id,
				),
			),
			'post_status'      => 'any',
			'suppress_filters' => 0,
		);

		$category_questions = get_posts( $args );
		if ( empty( $category_questions ) ) {
			return;
		}

		foreach ( $category_questions as $question ) {
			$has_translation = $this->has_translation_in_language( $question->ID, 'post_' . $question->post_type, $target_language );
			if ( $has_translation ) {
				continue;
			}
			$this->copy_post_to_language( $question->ID, $target_language, true );
		}
	}
}
