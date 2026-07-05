<?php
/**
 * File containing the Lesson_Quiz_Duplicator class.
 *
 * @package sensei
 */

namespace Sensei\Admin\Content_Duplicators;

/**
 * Class Lesson_Quiz_Duplicator.
 *
 * @since 4.21.0
 */
class Lesson_Quiz_Duplicator {
	/**
	 * Post duplicator instance.
	 *
	 * @var Post_Duplicator
	 */
	private Post_Duplicator $post_duplicator;

	/**
	 * Lesson_Quiz_Duplicator constructor.
	 */
	public function __construct() {
		$this->post_duplicator = new Post_Duplicator();
	}

	/**
	 * Duplicate quizzes inside lessons.
	 *
	 * @param int $old_lesson_id ID of original lesson.
	 * @param int $new_lesson_id ID of duplicate lesson.
	 */
	public function duplicate( int $old_lesson_id, int $new_lesson_id ): void {
		$old_quiz_id = Sensei()->lesson->lesson_quizzes( $old_lesson_id );

		if ( empty( $old_quiz_id ) ) {
			return;
		}

		$old_quiz = get_post( $old_quiz_id );
		if ( ! $old_quiz instanceof \WP_Post ) {
			return;
		}

		$old_quiz_questions = Sensei()->lesson->lesson_quiz_questions( $old_quiz_id );

		// Duplicate the generic wp post information.
		$new_quiz = $this->post_duplicator->duplicate( $old_quiz, '' );

		if ( ! $new_quiz ) {
			return;
		}

		// Update the new lesson data.
		add_post_meta( $new_lesson_id, '_lesson_quiz', $new_quiz->ID );

		// Update the new quiz data.
		add_post_meta( $new_quiz->ID, '_quiz_lesson', $new_lesson_id );
		wp_update_post(
			array(
				'ID'          => $new_quiz->ID,
				'post_parent' => $new_lesson_id,
			)
		);

		$old_to_new_question_map = array();

		foreach ( $old_quiz_questions as $question ) {

			// Duplicate the question post so edits don't affect the original.
			$new_question = $this->post_duplicator->duplicate( $question, '' );

			if ( $new_question ) {
				$old_to_new_question_map[ $question->ID ] = $new_question->ID;

				// Set the quiz association on the new question.
				add_post_meta( $new_question->ID, '_quiz_id', $new_quiz->ID, true );

				// Copy the question order for the new quiz.
				$old_question_order = get_post_meta( $question->ID, '_quiz_question_order' . $old_quiz_id, true );
				$new_question_order = str_ireplace( (string) $old_quiz_id, (string) $new_quiz->ID, $old_question_order );
				add_post_meta( $new_question->ID, '_quiz_question_order' . $new_quiz->ID, $new_question_order );
			}
		}

		// Update the question order meta on the new quiz to reference new question IDs.
		$old_question_order = get_post_meta( $old_quiz_id, '_question_order', true );
		if ( is_array( $old_question_order ) ) {
			$new_question_order = array();
			foreach ( $old_question_order as $old_q_id ) {
				$old_q_id = (int) $old_q_id;
				if ( isset( $old_to_new_question_map[ $old_q_id ] ) ) {
					$new_question_order[] = (string) $old_to_new_question_map[ $old_q_id ];
				}
			}
			update_post_meta( $new_quiz->ID, '_question_order', $new_question_order );
		}

		// Update the duplicated lesson's post_content to reference the new quiz ID
		// in the quiz block, and new question IDs in question blocks.
		$this->update_lesson_content_with_new_ids( $new_lesson_id, $old_quiz_id, $new_quiz->ID, $old_to_new_question_map );
	}

	/**
	 * Replace old quiz/question IDs in the lesson's post_content block markup.
	 *
	 * @param int   $lesson_id              The duplicated lesson ID.
	 * @param int   $old_quiz_id            The original quiz ID.
	 * @param int   $new_quiz_id            The new quiz ID.
	 * @param array $old_to_new_question_map Map of old question ID => new question ID.
	 */
	private function update_lesson_content_with_new_ids( int $lesson_id, int $old_quiz_id, int $new_quiz_id, array $old_to_new_question_map ): void {
		$lesson = get_post( $lesson_id );
		if ( ! $lesson || empty( $lesson->post_content ) ) {
			return;
		}

		$content = $lesson->post_content;

		// Replace the quiz block id attribute.
		$content = preg_replace_callback(
			'/<!-- wp:sensei-lms\/quiz\s+(\{[^}]*\})\s+-->/',
			function ( $matches ) use ( $old_quiz_id, $new_quiz_id ) {
				$attrs = json_decode( $matches[1], true );
				if ( isset( $attrs['id'] ) && (int) $attrs['id'] === (int) $old_quiz_id ) {
					$attrs['id'] = $new_quiz_id;
					return '<!-- wp:sensei-lms/quiz ' . wp_json_encode( $attrs ) . ' -->';
				}
				return $matches[0];
			},
			$content
		);

		// Replace question block id attributes.
		foreach ( $old_to_new_question_map as $old_id => $new_id ) {
			$content = preg_replace_callback(
				'/<!-- wp:sensei-lms\/quiz-question\s+(\{[^}]*\})\s+-->/',
				function ( $matches ) use ( $old_id, $new_id ) {
					$attrs = json_decode( $matches[1], true );
					if ( isset( $attrs['id'] ) && (int) $attrs['id'] === (int) $old_id ) {
						$attrs['id'] = $new_id;
						return '<!-- wp:sensei-lms/quiz-question ' . wp_json_encode( $attrs ) . ' -->';
					}
					return $matches[0];
				},
				$content
			);
		}

		if ( $content !== $lesson->post_content ) {
			wp_update_post(
				array(
					'ID'           => $lesson_id,
					'post_content' => $content,
				)
			);
		}
	}
}
