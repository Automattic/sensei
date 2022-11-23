<?php
/**
 * File containing the class Sensei_Update_Remove_Abandoned_Multiple_Question.
 *
 * @since 3.9.0
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove abandoned multiple_question posts.
 */
class Sensei_Update_Remove_Abandoned_Multiple_Question extends Sensei_Background_Job_Batch {
	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size() : int {
		return 30;
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
		$query     = $this->get_multiple_question_query( $offset );
		$remaining = $query->found_posts - $offset;

		foreach ( $query->posts as $question ) {
			if ( 'multiple_question' === $question->post_type && $this->is_abandoned( $question ) ) {
				wp_delete_post( $question->ID, true );
			}

			$remaining--;
		}

		return $remaining > 0;
	}

	/**
	 * Check to see if a post is abandoned.
	 *
	 * @param WP_Post $question Question post to check.
	 *
	 * @return bool
	 */
	private function is_abandoned( WP_Post $question ) : bool {
		$quizzes    = array_filter( get_post_meta( $question->ID, '_quiz_id', false ) );
		$quiz_found = false;

		foreach ( $quizzes as $quiz_id ) {
			if ( 'quiz' === get_post_type( $quiz_id ) ) {
				$quiz_found = true;
				break;
			}
		}

		return ! $quiz_found;
	}

	/**
	 * Get the abandoned `multiple_question` posts.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return WP_Query
	 */
	protected function get_multiple_question_query( int $offset ) : WP_Query {
		return new WP_Query(
			[
				'post_type'      => 'multiple_question',
				'post_status'    => 'any',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'offset'         => (int) $offset,
				'posts_per_page' => $this->get_batch_size(),
			]
		);
	}
}
