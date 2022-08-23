<?php
/**
 * File containing the Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Models;

use DateTime;
use Sensei_Lesson;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress.
 *
 * @since $$next-version$$
 */
class Lesson_Progress_Comments implements Lesson_Progress_Interface {
	/**
	 * Status lesson in progress.
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status lesson completed.
	 */
	public const STATUS_COMPLETE = 'complete';

	/**
	 * Progress identifier.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Lesson identifier.
	 *
	 * @var int
	 */
	protected $lesson_id;

	/**
	 * User identifier.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Progress data.
	 *
	 * @var string|null
	 */
	protected $status;

	/**
	 * Course start date.
	 *
	 * @var DateTime|null
	 */
	protected $started_at;

	/**
	 * Course completion date.
	 *
	 * @var DateTime|null
	 */
	protected $completed_at;

	/**
	 * Course progress created date.
	 *
	 * @var DateTime
	 */
	protected $created_at;

	/**
	 * Course progress updated date.
	 *
	 * @var DateTime
	 */
	protected $updated_at;

	/**
	 * Sensei_Lesson_Progress constructor.
	 *
	 * @param int           $id         Progress identifier.
	 * @param int           $lesson_id  Lesson identifier.
	 * @param int           $user_id    User identifier.
	 * @param string|null   $status     Progress status.
	 * @param DateTime|null $started_at     Course start date.
	 * @param DateTime|null $completed_at   Course completion date.
	 * @param DateTime      $created_at     Course progress created date.
	 * @param DateTime      $updated_at     Course progress updated date.
	 */
	public function __construct( int $id, int $lesson_id, int $user_id, ?string $status, ?DateTime $started_at, ?DateTime $completed_at, DateTime $created_at, DateTime $updated_at ) {
		$this->id           = $id;
		$this->lesson_id    = $lesson_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->created_at   = $created_at;
		$this->updated_at   = $updated_at;
	}

	/**
	 * Changes the lesson progress status and start date.
	 *
	 * @param DateTime|null $started_at The start date.
	 */
	public function start( ?DateTime $started_at = null ): void {
		$this->started_at = $started_at ?? new DateTime();
		$this->status     = self::STATUS_IN_PROGRESS;
	}

	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @param DateTime|null $completed_at The completion date.
	 */
	public function complete( ?DateTime $completed_at = null ): void {
		$this->completed_at = $completed_at ?? new DateTime();
		$has_questions      = Sensei_Lesson::lesson_quiz_has_questions( $this->lesson_id );
		$this->status       = $has_questions ? Quiz_Progress::STATUS_PASSED : self::STATUS_COMPLETE;
	}

	/**
	 * Returns the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Returns the lesson identifier.
	 *
	 * @return int
	 */
	public function get_lesson_id(): int {
		return $this->lesson_id;
	}

	/**
	 * Returns the user identifier.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Returns the lesson progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Returns the lesson start date.
	 *
	 * @return DateTime|null
	 */
	public function get_started_at(): ?DateTime {
		return $this->started_at;
	}

	/**
	 * Returns the lesson completion date.
	 *
	 * @return DateTime|null
	 */
	public function get_completed_at(): ?DateTime {
		return $this->completed_at;
	}

	/**
	 * Returns if the lesson progress is complete.
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		$completed_statuses = [
			self::STATUS_COMPLETE,
			Quiz_Progress::STATUS_PASSED,
			Quiz_Progress::STATUS_GRADED,
		];

		return in_array( $this->status, $completed_statuses, true );
	}
}
