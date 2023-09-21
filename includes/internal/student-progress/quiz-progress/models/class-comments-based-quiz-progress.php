<?php
/**
 * File containing the Comments_Based_Quiz_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Quiz_Progress.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Quiz_Progress extends Quiz_Progress_Abstract {
	/**
	 * Get the progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		$supported_statuses = [
			self::STATUS_IN_PROGRESS,
			self::STATUS_FAILED,
			self::STATUS_GRADED,
			self::STATUS_PASSED,
			self::STATUS_UNGRADED,
		];

		$status = in_array( $this->status, $supported_statuses, true )
			? $this->status
			: self::STATUS_IN_PROGRESS;

		return $status;
	}
}
