<?php
/**
 * File containing the class Sensei_Update_Fix_Question_Author.
 *
 * @since 3.9.0
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fix question post authors from previous course teacher changes.
 */
class Sensei_Update_Fix_Question_Author extends Sensei_Background_Job_Batch {
	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size() : int {
		return 10;
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
		$query     = $this->get_quiz_query( $offset );
		$remaining = $query->found_posts - $offset;

		foreach ( $query->posts as $quiz ) {
			Sensei()->quiz->update_quiz_author( $quiz->ID, $quiz->post_author );
			$remaining--;
		}

		return $remaining > 0;
	}

	/**
	 * Get the quiz offset.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return WP_Query
	 */
	protected function get_quiz_query( int $offset ) : WP_Query {
		return new WP_Query(
			[
				'post_type'      => 'quiz',
				'post_status'    => 'any',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'offset'         => (int) $offset,
				'posts_per_page' => $this->get_batch_size(),
			]
		);
	}
}
