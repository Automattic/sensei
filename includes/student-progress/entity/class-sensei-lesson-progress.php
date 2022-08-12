<?php
/**
 * File containing the Sensei_Lesson_Progress_Abstract class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Progress_Abstract.
 *
 * @since $$next-version$$
 */
class Sensei_Lesson_Progress implements Sensei_Lesson_Progress_Interface {
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
	 * Course progress metadata.
	 * Field exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @var array
	 */
	protected $metadata;

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
	 * @param array         $metadata   Course progress metadata. Field exists for compatibility with the legacy code and will be removed in later versions.
	 */
	public function __construct( int $id, int $lesson_id, int $user_id, ?string $status, ?DateTime $started_at, ?DateTime $completed_at, DateTime $created_at, DateTime $updated_at, array $metadata ) {
		$this->id           = $id;
		$this->lesson_id    = $lesson_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->created_at   = $created_at;
		$this->updated_at   = $updated_at;
		$this->metadata     = $metadata;
	}

	/**
	 * Changes the lesson progress status and start date.
	 *
	 * @param DateTime|null $started_at The start date.
	 */
	public function start( ?DateTime $started_at = null ): void {
		$this->started_at = $started_at ?? new DateTime();
		$this->status     = Sensei_Lesson_Progress_Interface::STATUS_IN_PROGRESS;
	}

	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @param DateTime|null $completed_at The completion date.
	 */
	public function complete( ?DateTime $completed_at = null ): void {
		$this->completed_at = $completed_at ?? new DateTime();
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
	 * Returns the lesson progress metadata.
	 *
	 * @return array The lesson progress metadata.
	 */
	public function get_metadata(): array {
		return $this->metadata;
	}
}
