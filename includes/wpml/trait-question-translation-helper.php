<?php
/**
 * File containing the \Sensei\WPML\Question_Translation_Helper trait.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Question_Translation_Helper
 *
 * @since 4.22.0
 *
 * @internal
 */
trait Question_Translation_Helper {
	/**
	 * Update question translations from lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	private function update_question_translations_from_lesson( $lesson_id ) {
		$details = (array) apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $lesson_id,
				'element_type' => 'lesson',
			)
		);

		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $details['language_code'] ) ) {
			return;
		}

		$lesson = get_post( $lesson_id );
		if ( ! $lesson ) {
			return;
		}

		$lesson_content = $lesson->post_content;
		if ( empty( $lesson_content ) ) {
			return;
		}

		$blocks = parse_blocks( $lesson_content );
		if ( empty( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block ) {
			if ( 'sensei-lms/question-block' !== $block['blockName'] ) {
				continue;
			}

			$question_id = $block['attrs']['id'] ?? 0;
			if ( empty( $question_id ) ) {
				continue;
			}

			$question_block = render_block( $block );

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$question_id = apply_filters( 'wpml_object_id', $question_id, 'question', false, $details['language_code'] );

			if ( empty( $question_id ) ) {
				continue;
			}

			// Update question content.
			wp_update_post(
				array(
					'ID'           => (int) $question_id,
					'post_content' => $question_block,
				)
			);
		}
	}
}
