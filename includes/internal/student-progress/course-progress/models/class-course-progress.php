<?php
/**
 * File containing the Course_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Course_Progress {
	/**
	 * Status course in progress.
	 *
	 * @internal
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status course complete.
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
	 * Course identifier.
	 *
	 * @var int
	 */
	protected $course_id;

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
	 * Course progress constructor.
	 *
	 * @internal
	 *
	 * @param int                    $id          Progress identifier.
	 * @param int                    $course_id Course identifier.
	 * @param int                    $user_id  User identifier.
	 * @param string|null            $status   Progress status.
	 * @param DateTimeInterface|null $started_at Course start date.
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 * @param DateTimeInterface      $created_at Course progress created date.
	 * @param DateTimeInterface      $updated_at Course progress updated date.
	 */
	public function __construct( int $id, int $course_id, int $user_id, ?string $status, ?DateTimeInterface $started_at, ?DateTimeInterface $completed_at, DateTimeInterface $created_at, DateTimeInterface $updated_at ) {
		$this->id           = $id;
		$this->course_id    = $course_id;
		$this->user_id      = $user_id;
		$this->status       = $status;
		$this->started_at   = $started_at;
		$this->completed_at = $completed_at;
		$this->created_at   = $created_at;
		$this->updated_at   = $updated_at;
	}

	/**
	 * Set in-progress status and start date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at Course start date.
	 */
	public function start( DateTimeInterface $started_at = null ): void {
		$this->status     = self::STATUS_IN_PROGRESS;
		$this->started_at = $started_at ?? current_datetime();
	}

	/**
	 * Set complete status and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 */
	public function complete( DateTimeInterface $completed_at = null ): void {
		$this->status       = self::STATUS_COMPLETE;
		$this->completed_at = $completed_at ?? current_datetime();
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
	 * Returns the course identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_course_id(): int {
		return $this->course_id;
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
	 * Returns the course progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Returns the course start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface {
		return $this->started_at;
	}

	/**
	 * Returns the course completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface {
		return $this->completed_at;
	}

	/**
	 * Returns the course progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->updated_at;
	}

	/**
	 * Returns the course progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * Set the course progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
