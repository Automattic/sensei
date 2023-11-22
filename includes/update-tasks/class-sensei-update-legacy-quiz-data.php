<?php
/**
 * File containing the class Sensei_Update_Legacy_Quiz_Data.
 *
 * @since 4.19.2
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update the legacy quiz data generated before version 1.7.4.
 */
class Sensei_Update_Legacy_Quiz_Data extends Sensei_Background_Job_Batch {
	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size() : int {
		return 20;
	}

	/**
	 * Can multiple instances be enqueued at the same time?
	 *
	 * @return bool
	 */
	protected function allow_multiple_instances() : bool {
		return false;
	}

	/**
	 * Run batch.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return bool Returns true if there is more to do.
	 */
	protected function run_batch( int $offset ) : bool {
		$answer_comments = $this->get_legacy_answers();
		$run_again       = count( $answer_comments ) === $this->get_batch_size();

		/**
		 * Loop through the legacy answers and update the data.
		 *
		 * @var WP_Comment $comment
		 */
		foreach ( $answer_comments as $comment ) {
			$answer_value = $comment->comment_content;
			$comment_id   = (int) $comment->comment_ID;
			$question_id  = (int) $comment->comment_post_ID;
			$user_id      = (int) $comment->user_id;
			$quiz_id      = (int) get_post_meta( $question_id, '_quiz_id', true );
			$points       = get_comment_meta( $comment_id, 'user_grade', true );
			$submission   = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id );
			$answer       = Sensei()->quiz_answer_repository->create( $submission, $question_id, $answer_value );

			if ( is_numeric( $points ) ) {
				$feedback = get_comment_meta( $comment_id, 'answer_note', true );
				$feedback = false === $feedback ? null : $feedback;

				Sensei()->quiz_grade_repository->create( $submission, $answer, $question_id, (int) $points, $feedback );
			}

			wp_delete_comment( $comment ); // Soft delete.
		}

		return $run_again;
	}

	/**
	 * Get the legacy answer comments.
	 *
	 * @return array An array of comments holding the legacy answers.
	 */
	protected function get_legacy_answers() : array {
		$comments = Sensei_Utils::sensei_check_for_activity(
			array(
				'type'   => 'sensei_user_answer',
				'number' => $this->get_batch_size(),
				'status' => 'log',
				'order'  => 'ASC',
			),
			true
		);

		if ( is_array( $comments ) ) {
			return $comments;
		}

		return array( $comments );
	}
}
