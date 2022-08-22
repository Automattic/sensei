<?php
/**
 * File containing the Sensei_Course_Progress_Tables class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Progress_Tables.
 *
 * @since $$next-version$$
 */
class Course_Progress_Tables extends Course_Progress_Abstract {
	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTime|null $started_at Course start date.
	 */
	public function start( DateTime $started_at = null ): void {
		$this->status     = 'in-progress';
		$this->started_at = $started_at ?? new DateTime();
	}

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTime|null $completed_at Course completion date.
	 */
	public function complete( DateTime $completed_at = null ): void {
		$this->status       = 'complete';
		$this->completed_at = $completed_at ?? new DateTime();
	}
}
