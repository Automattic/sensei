<?php
/**
 * File containing the Sensei_Course_Progress_Interface interface.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Sensei_Course_Progress_Interface.
 *
 * @since $$next-version$$
 */
interface Course_Progress_Interface {
	/**
	 * Status course in progress.
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status course complete.
	 */
	public const STATUS_COMPLETE = 'complete';

	/**
	 * Set in-progress status and start date.
	 *
	 * @param DateTimeInterface|null $started_at Course start date.
	 */
	public function start( DateTimeInterface $started_at = null ): void;

	/**
	 * Set complete status and completion date.
	 *
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 */
	public function complete( DateTimeInterface $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Returns the course identifier.
	 *
	 * @return int
	 */
	public function get_course_id(): int;

	/**
	 * Returns the user identifier.
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Returns the course progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns the course start date.
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface;

	/**
	 * Returns the course completion date.
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface;

	/**
	 * Returns the course progress created date.
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface;

	/**
	 * Returns the course progress updated date.
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface;

	/**
	 * Returns the course progress metadata.
	 * Method exists for compatibility with the legacy code and will be removed in later versions.
	 *
	 * @return array Course progress metadata.
	 */
	public function get_metadata(): array;

	/**
	 * Set the course progress updated date.
	 *
	 * @param DateTimeInterface $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void;
}
