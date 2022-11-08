<?php
/**
 * File containing the Lesson_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Models;

use DateTimeInterface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
use Sensei_Lesson;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Lesson_Progress {
	/**
	 * Status lesson in progress.
	 *
	 * @internal
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status lesson completed.
	 *
	 * @internal
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
	 * Lesson progress constructor.
	 *
	 * @internal
	 *
	 * @param int                    $id         Progress identifier.
	 * @param int                    $lesson_id  Lesson identifier.
	 * @param int                    $user_id    User identifier.
	 * @param string|null            $status     Progress status.
	 * @param DateTimeInterface|null $started_at     Course start date.
	 * @param DateTimeInterface|null $completed_at   Course completion date.
	 * @param DateTimeInterface      $created_at     Course progress created date.
	 * @param DateTimeInterface      $updated_at     Course progress updated date.
	 */
	public function __construct( int $id, int $lesson_id, int $user_id, ?string $status, ?DateTimeInterface $started_at, ?DateTimeInterface $completed_at, DateTimeInterface $created_at, DateTimeInterface $updated_at ) {
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
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at The start date.
	 */
	public function start( ?DateTimeInterface $started_at = null ): void {
		$this->started_at = $started_at ?? current_datetime();
		$this->status     = self::STATUS_IN_PROGRESS;
	}

	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $completed_at The completion date.
	 */
	public function complete( ?DateTimeInterface $completed_at = null ): void {
		$this->completed_at = $completed_at ?? current_datetime();
		$has_questions      = Sensei_Lesson::lesson_quiz_has_questions( $this->lesson_id );
		$this->status       = $has_questions ? Quiz_Progress::STATUS_PASSED : self::STATUS_COMPLETE;
	}

	/**
	 * Returns the progress identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Returns the lesson identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_lesson_id(): int {
		return $this->lesson_id;
	}

	/**
	 * Returns the user identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Returns the lesson progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Returns the lesson start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface {
		return $this->started_at;
	}

	/**
	 * Returns the lesson completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface {
		return $this->completed_at;
	}

	/**
	 * Returns if the lesson progress is complete.
	 *
	 * @internal
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

	/**
	 * Returns the lesson progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * Returns the lesson progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->updated_at;
	}

	/**
	 * Set lesson progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at The updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
