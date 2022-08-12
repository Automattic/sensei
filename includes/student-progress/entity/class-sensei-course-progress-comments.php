<?php
/**
 * File containing the Sensei_Course_Progress_Comments class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Progress_Comments.
 *
 * @since $$next-version$$
 */
class Sensei_Course_Progress_Comments extends Sensei_Course_Progress_Abstract {
	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTime|null $started_at Course start date.
	 */
	public function start( DateTime $started_at = null ): void {
		$this->status     = 'in-progress';
		$this->started_at = $started_at ?? new DateTime();
		$this->metadata   = array_replace(
			$this->metadata,
			[
				'complete' => 0,
				'percent'  => 0,
			]
		);
	}

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTime|null $completed_at Course completion date.
	 */
	public function complete( DateTime $completed_at = null ): void {
		$this->status       = 'complete';
		$this->completed_at = $completed_at ?? new DateTime();

		$course_lesson_ids = Sensei()->course->course_lessons( $this->get_course_id(), 'any', 'ids' );

		$metadata       = [
			'percent'  => 100,
			'complete' => count( $course_lesson_ids ),
		];
		$this->metadata = array_replace( $this->metadata, $metadata );
	}
}
