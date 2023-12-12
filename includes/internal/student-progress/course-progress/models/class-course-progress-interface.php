<?php
/**
 * File containing the Course_Progress_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interfaces Course_Progress_Interface.
 *
 * @internal
 *
 * @since 4.18.0
 */
interface Course_Progress_Interface {
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
	 * Set in-progress status and start date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at Course start date.
	 */
	public function start( DateTimeInterface $started_at = null ): void;

	/**
	 * Set complete status and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $completed_at Course completion date.
	 */
	public function complete( DateTimeInterface $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Returns the course identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_course_id(): int;

	/**
	 * Returns the user identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Returns the course progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns the course start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface;

	/**
	 * Returns the course completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface;

	/**
	 * Returns the course progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface;

	/**
	 * Returns the course progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface;

	/**
	 * Set the course progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at Course progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void;
}
