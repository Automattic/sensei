<?php
/**
 * File containing the Comments_Based_Course_Progress class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Course_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Course_Progress.
 *
 * @since $$next-version$$
 */
class Comments_Based_Course_Progress implements Course_Progress_Interface {
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
	 * Course progress metadata.
	 * Field exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @var array
	 */
	protected $metadata;

	/**
	 * Course progress constructor.
	 *
	 * @param int                    $id          Progress identifier.
	 * @param int                    $course_id Course identifier.
	 * @param int                    $user_id  User identifier.
	 * @param DateTimeInterface      $created_at Course progress created date.
	 * @param string|null            $status   Progress status.
	 * @param DateTimeInterface|null $started_at Course start date.
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 * @param DateTimeInterface|null $updated_at Course progress updated date.
	 * @param array                  $metadata Course progress metadata.
	 */
	public function __construct( int $id, int $course_id, int $user_id, DateTimeInterface $created_at, string $status = null, DateTimeInterface $started_at = null, DateTimeInterface $completed_at = null, DateTimeInterface $updated_at = null, array $metadata = [] ) {
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
	 * @param DateTimeInterface|null $started_at Course start date.
	 */
	public function start( DateTimeInterface $started_at = null ): void {
		$this->status     = 'in-progress';
		$this->started_at = $started_at ?? current_datetime();
		$this->metadata   = array_replace(
			$this->metadata,
			[
				'complete' => 0,
				'percent'  => 0,
			]
		);
	}

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 */
	public function complete( DateTimeInterface $completed_at = null ): void {
		$this->status       = 'complete';
		$this->completed_at = $completed_at ?? current_datetime();
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
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface {
		return $this->started_at;
	}

	/**
	 * Returns the course completion date.
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface {
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
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->updated_at;
	}

	/**
	 * Returns the course progress created date.
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * Set the course progress updated date.
	 *
	 * @param DateTimeInterface $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
