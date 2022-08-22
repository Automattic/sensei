<?php
/**
 * File containing the Sensei_Quiz_Progress class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Quiz_Progress.
 *
 * @since $$next-version$$
 */
class Quiz_Progress {
	/**
	 * In progress quiz status.
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Passed quiz status.
	 */
	public const STATUS_PASSED = 'passed';

	/**
	 * Graded quiz status.
	 */
	public const STATUS_GRADED = 'graded';

	/**
	 * Ungraded quiz status.
	 */
	public const STATUS_UNGRADED = 'ungraded';

	/**
	 * Failed quiz status.
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Progress identifier.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Quiz identifier.
	 *
	 * @var int
	 */
	protected $quiz_id;

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
	 * @param int           $id Progress identifier.
	 * @param int           $quiz_id Quiz identifier.
	 * @param int           $user_id User identifier.
	 * @param string|null   $status Progress status.
	 * @param DateTime|null $started_at Quiz start date.
	 * @param DateTime|null $completed_at Quiz completion date.
	 * @param DateTime      $created_at Quiz progress created date.
	 * @param DateTime      $updated_at Quiz progress updated date.
	 */
	public function __construct( int $id, int $quiz_id, int $user_id, ?string $status, ?DateTime $started_at, ?DateTime $completed_at, DateTime $created_at, DateTime $updated_at ) {
		$this->id           = $id;
		$this->quiz_id      = $quiz_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->created_at   = $created_at;
		$this->updated_at   = $updated_at;
	}

	/**
	 * Set the status of the quiz to 'in-progress' and start date.
	 *
	 * @param DateTime|null $started_at Quiz start date.
	 */
	public function start( ?DateTime $started_at = null ): void {
		$this->status     = self::STATUS_IN_PROGRESS;
		$this->started_at = $started_at ?? new DateTime();
	}

	/**
	 * Set the status of the quiz to 'passed' and completion date.
	 *
	 * @param DateTime|null $passed_at Quiz completion date.
	 */
	public function pass( ?DateTime $passed_at = null ): void {
		$this->status       = self::STATUS_PASSED;
		$this->completed_at = $passed_at ?? new DateTime();
	}

	/**
	 * Set the status of the quiz to 'graded' and completion date.
	 *
	 * @param DateTime|null $graded_at Quiz completion date.
	 */
	public function grade( ?DateTime $graded_at = null ): void {
		$this->status       = self::STATUS_GRADED;
		$this->completed_at = $graded_at ?? new DateTime();
	}

	/**
	 * Set the status of the quiz to 'ungraded' and reset completion date.
	 */
	public function ungrade(): void {
		$this->status       = self::STATUS_UNGRADED;
		$this->completed_at = null;
	}

	/**
	 * Set the status of the quiz to 'failed' and reset completion date.
	 */
	public function fail(): void {
		$this->status       = self::STATUS_FAILED;
		$this->completed_at = null;
	}

	/**
	 * Get the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the quiz identifier.
	 *
	 * @return int
	 */
	public function get_quiz_id(): int {
		return $this->quiz_id;
	}

	/**
	 * Get the user identifier.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Get the progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Get the quiz start date.
	 *
	 * @return DateTime|null
	 */
	public function get_started_at(): ?DateTime {
		return $this->started_at;
	}

	/**
	 * Get the quiz completion date.
	 *
	 * @return DateTime|null
	 */
	public function get_completed_at(): ?DateTime {
		return $this->completed_at;
	}

	/**
	 * Get the quiz progress created date.
	 *
	 * @return DateTime
	 */
	public function get_created_at(): DateTime {
		return $this->created_at;
	}

	/**
	 * Get the quiz progress updated date.
	 *
	 * @return DateTime
	 */
	public function get_updated_at(): DateTime {
		return $this->updated_at;
	}
}
