<?php
/**
 * File containing the Quiz_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Quiz_Progress.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Quiz_Progress {
	/**
	 * In progress quiz status.
	 *
	 * @internal
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Passed quiz status.
	 *
	 * @internal
	 */
	public const STATUS_PASSED = 'passed';

	/**
	 * Graded quiz status.
	 *
	 * @internal
	 */
	public const STATUS_GRADED = 'graded';

	/**
	 * Ungraded quiz status.
	 *
	 * @internal
	 */
	public const STATUS_UNGRADED = 'ungraded';

	/**
	 * Failed quiz status.
	 *
	 * @internal
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
	 * @var DateTimeInterface|null
	 */
	protected $started_at;

	/**
	 * Course completion date.
	 *
	 * @var DateTimeInterface|null
	 */
	protected $completed_at;

	/**
	 * Course progress created date.
	 *
	 * @var DateTimeInterface
	 */
	protected $created_at;

	/**
	 * Course progress updated date.
	 *
	 * @var DateTimeInterface
	 */
	protected $updated_at;

	/**
	 * Sensei_Lesson_Progress constructor.
	 *
	 * @internal
	 *
	 * @param int                    $id Progress identifier.
	 * @param int                    $quiz_id Quiz identifier.
	 * @param int                    $user_id User identifier.
	 * @param string|null            $status Progress status.
	 * @param DateTimeInterface|null $started_at Quiz start date.
	 * @param DateTimeInterface|null $completed_at Quiz completion date.
	 * @param DateTimeInterface      $created_at Quiz progress created date.
	 * @param DateTimeInterface      $updated_at Quiz progress updated date.
	 */
	public function __construct( int $id, int $quiz_id, int $user_id, ?string $status, ?DateTimeInterface $started_at, ?DateTimeInterface $completed_at, DateTimeInterface $created_at, DateTimeInterface $updated_at ) {
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
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at Quiz start date.
	 */
	public function start( ?DateTimeInterface $started_at = null ): void {
		$this->status     = self::STATUS_IN_PROGRESS;
		$this->started_at = $started_at ?? current_datetime();
	}

	/**
	 * Set the status of the quiz to 'passed' and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $passed_at Quiz completion date.
	 */
	public function pass( ?DateTimeInterface $passed_at = null ): void {
		$this->status       = self::STATUS_PASSED;
		$this->completed_at = $passed_at ?? current_datetime();
	}

	/**
	 * Set the status of the quiz to 'graded' and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $graded_at Quiz completion date.
	 */
	public function grade( ?DateTimeInterface $graded_at = null ): void {
		$this->status       = self::STATUS_GRADED;
		$this->completed_at = $graded_at ?? current_datetime();
	}

	/**
	 * Set the status of the quiz to 'ungraded' and reset completion date.
	 *
	 * @internal
	 */
	public function ungrade(): void {
		$this->status       = self::STATUS_UNGRADED;
		$this->completed_at = null;
	}

	/**
	 * Set the status of the quiz to 'failed' and reset completion date.
	 *
	 * @internal
	 */
	public function fail(): void {
		$this->status       = self::STATUS_FAILED;
		$this->completed_at = null;
	}

	/**
	 * Get the progress identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the quiz identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_quiz_id(): int {
		return $this->quiz_id;
	}

	/**
	 * Get the user identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Get the progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Get the quiz start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface {
		return $this->started_at;
	}

	/**
	 * Get the quiz completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface {
		return $this->completed_at;
	}

	/**
	 * Get the quiz progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * Get the quiz progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->updated_at;
	}

	/**
	 * Set the quiz progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at Quiz progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
