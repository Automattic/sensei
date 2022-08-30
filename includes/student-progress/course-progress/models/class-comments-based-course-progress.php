<?php
/**
 * File containing the Comments_Based_Course_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Course_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Course_Progress.
 *
 * @since $$next-version$$
 */
class Comments_Based_Course_Progress extends Course_Progress_Abstract {
	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTimeInterface|null $started_at Course start date.
	 */
	public function start( DateTimeInterface $started_at = null ): void {
		$this->status     = 'in-progress';
		$this->started_at = $started_at ?? current_datetime();
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
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 */
	public function complete( DateTimeInterface $completed_at = null ): void {
		$this->status       = 'complete';
		$this->completed_at = $completed_at ?? current_datetime();
	}
}
