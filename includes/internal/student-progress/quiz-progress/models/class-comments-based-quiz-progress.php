<?php
/**
 * File containing the Comments_Based_Quiz_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Models;

use DateTimeInterface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Quiz_Progress.
 *
 * @internal
 *
 * @since 4.18.0
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
		switch ( $this->status ) {
			case self::STATUS_IN_PROGRESS:
			case self::STATUS_FAILED:
			case self::STATUS_GRADED:
			case self::STATUS_PASSED:
			case self::STATUS_UNGRADED:
				return $this->status;
			case Lesson_Progress_Interface::STATUS_COMPLETE:
				return self::STATUS_PASSED;
			default:
				return self::STATUS_IN_PROGRESS;
		}
	}
}
