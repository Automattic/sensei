<?php
/**
 * File containing the Sensei_Course_Progress_Abstract class.
 */

abstract class Sensei_Course_Progress_Abstract implements Sensei_Course_Progress_Interface {

	/**
	 * Progress identifier.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Course identifier.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * User identifier.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Progress data.
	 *
	 * @var string|null
	 */
	private $status;

	/**
	 * Course start date.
	 *
	 * @var DateTime|null
	 */
	private $started_at;

	/**
	 * Course completion date.
	 *
	 * @var DateTime|null
	 */
	private $completed_at;

	/**
	 * Course progress created date.
	 *
	 * @var DateTime
	 */
	private $created_at;

	/**
	 * Course progress updated date.
	 *
	 * @var DateTime
	 */
	private $updated_at;

	/**
	 * Course progress metadata.
	 * Field exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @var array
	 */
	private $metadata;

	/**
	 * Course progress constructor.
	 *
	 * @param int           $id          Progress identifier.
	 * @param int           $course_id Course identifier.
	 * @param int           $user_id  User identifier.
	 * @param DateTime      $created_at Course progress created date.
	 * @param string|null   $status   Progress status.
	 * @param DateTime|null $started_at Course start date.
	 * @param DateTime|null $completed_at Course completion date.
	 * @param DateTime|null $updated_at Course progress updated date.
	 * @param array         $metadata Course progress metadata.
	 */
	public function __construct( int $id, int $course_id, int $user_id, DateTime $created_at, string $status = null, DateTime $started_at = null, DateTime $completed_at = null, DateTime $updated_at = null, array $metadata = [] ) {
		$this->id           = $id;
		$this->course_id    = $course_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->created_at   = $created_at;
		$this->updated_at   = $updated_at ?? $created_at;
		$this->metadata     = $metadata;
	}

	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTime|null $started_at Course start date.
	 */
	abstract public function start( DateTime $started_at = null ): void;

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTime|null $completed_at Course completion date.
	 */
	abstract public function complete( DateTime $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Returns the course identifier.
	 *
	 * @return int
	 */
	public function get_course_id(): int {
		return $this->course_id;
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
	 * Returns the course progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Returns the course start date.
	 *
	 * @return DateTime|null
	 */
	public function get_started_at(): ?DateTime {
		return $this->started_at;
	}

	/**
	 * Returns the course completion date.
	 *
	 * @return DateTime|null
	 */
	public function get_completed_at(): ?DateTime {
		return $this->completed_at;
	}

	/**
	 * Returns the course progress metadata.
	 * Method exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @return array Course progress metadata.
	 */
	public function get_metadata(): array {
		return $this->metadata;
	}

	/**
	 * Returns the course progress updated date.
	 *
	 * @return DateTime
	 */
	public function get_updated_at(): DateTime {
		return $this->updated_at;
	}

	/**
	 * Returns the course progress created date.
	 *
	 * @return DateTime
	 */
	public function get_created_at(): DateTime {
		return $this->created_at;
	}

	/**
	 * Set the course progress updated date.
	 *
	 * @param DateTime $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTime $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
