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

		foreach ( $old_quiz_questions as $question ) {

			// Copy the question order over to the new quiz.
			$old_question_order = get_post_meta( $question->ID, '_quiz_question_order' . $old_quiz_id, true );
			$new_question_order = str_ireplace( (string) $old_quiz_id, (string) $new_quiz->ID, $old_question_order );
			add_post_meta( $question->ID, '_quiz_question_order' . $new_quiz->ID, $new_question_order );

			// Add question to quiz.
			add_post_meta( $question->ID, '_quiz_id', $new_quiz->ID, false );

		}
	}
}
