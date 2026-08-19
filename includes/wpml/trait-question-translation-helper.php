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
	use WPML_API;

	/**
	 * Translated lessons whose question sync is waiting for WPML to write the
	 * translated lesson content.
	 *
	 * @var array<int, bool>
	 */
	private $lessons_pending_question_sync = array();

	/**
	 * Defer the question sync until WPML writes the translated lesson content.
	 *
	 * When the translation-completed hook fires for a block-based lesson the
	 * translated content does not exist yet; WPML assembles and writes it later
	 * in the same request. Mark the lesson so the sync runs when that write
	 * happens.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Translated lesson ID.
	 */
	private function defer_question_translations_update( $lesson_id ) {
		$this->lessons_pending_question_sync[ (int) $lesson_id ] = true;
	}

	/**
	 * Run the deferred question sync once the translated lesson content lands.
	 *
	 * Hooked to `wp_after_insert_post`, which fires once a post is fully written
	 * (content, meta, and terms). Only acts on lessons marked by
	 * defer_question_translations_update(), and only once the write carries
	 * content.
	 *
	 * @since $$next-version$$
	 *
	 * @internal
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object as written.
	 */
	public function update_question_translations_on_lesson_content_written( $post_id, $post ) {
		if ( empty( $this->lessons_pending_question_sync[ $post_id ] ) ) {
			return;
		}

		if ( '' === $post->post_content ) {
			return;
		}

		unset( $this->lessons_pending_question_sync[ $post_id ] );

		$this->update_question_translations_from_lesson( $post_id );
	}

	/**
	 * Update question translations from lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	private function update_question_translations_from_lesson( $lesson_id ) {
		$details = $this->get_element_language_details( $lesson_id, 'lesson' );
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

		foreach ( $this->find_question_blocks( $blocks ) as $block ) {
			$question_id = $block['attrs']['id'] ?? 0;
			if ( empty( $question_id ) ) {
				continue;
			}

			$question_id = $this->get_object_id( $question_id, 'question', false, $details['language_code'] );
			if ( empty( $question_id ) ) {
				continue;
			}

			$question_updates = array();

			// The translated title lives in the block attribute.
			if ( ! empty( $block['attrs']['title'] ) ) {
				$question_updates['post_title'] = (string) $block['attrs']['title'];
			}

			// A question post stores the question block's whole inner content
			// (description, answers, and feedback blocks), mirroring how the editor saves it
			// (see getBlockContent in the quiz editor and the REST question helpers).
			if ( ! empty( $block['innerBlocks'] ) ) {
				$question_updates['post_content'] = implode( '', array_map( 'serialize_block', $block['innerBlocks'] ) );
			}

			if ( $question_updates ) {
				$question_updates['ID'] = (int) $question_id;
				wp_update_post( $question_updates );
			}

			// The translated answers live in the block, split into correct and
			// incorrect. Recompute the answer order (the md5 of each label, in
			// block order) and the counts so they match the translated labels,
			// mirroring how the editor stores them.
			$answers = $block['attrs']['answer']['answers'] ?? array();
			if ( ! empty( $answers ) ) {
				$right_answers = array();
				$wrong_answers = array();
				$answer_order  = array();
				foreach ( $answers as $answer ) {
					if ( ! isset( $answer['label'] ) ) {
						continue;
					}
					if ( ! empty( $answer['correct'] ) ) {
						$right_answers[] = $answer['label'];
					} else {
						$wrong_answers[] = $answer['label'];
					}
					$answer_order[] = \Sensei()->lesson->get_answer_id( $answer['label'] );
				}
				update_post_meta( $question_id, '_question_right_answer', $right_answers );
				update_post_meta( $question_id, '_question_wrong_answers', $wrong_answers );
				update_post_meta( $question_id, '_answer_order', implode( ',', $answer_order ) );
				update_post_meta( $question_id, '_right_answer_count', count( $right_answers ) );
				update_post_meta( $question_id, '_wrong_answer_count', count( $wrong_answers ) );
			}
		}
	}

	/**
	 * Collect quiz-question blocks at any nesting depth. They are nested inside
	 * the quiz block in the lesson content.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array Question blocks.
	 */
	private function find_question_blocks( $blocks ) {
		$question_blocks = array();
		foreach ( $blocks as $block ) {
			if ( 'sensei-lms/quiz-question' === ( $block['blockName'] ?? null ) ) {
				$question_blocks[] = $block;
				continue;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$question_blocks = array_merge( $question_blocks, $this->find_question_blocks( $block['innerBlocks'] ) );
			}
		}
		return $question_blocks;
	}
}
