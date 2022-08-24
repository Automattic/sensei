<?php
/**
 * File containing the Lesson_Progress_Interface.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Models;

use DateTimeInterface;

/**
 * Class Lesson_Progress.
 *
 * @since $$next-version$$
 */
interface Lesson_Progress_Interface {
	/**
	 * Changes the lesson progress status and start date.
	 *
	 * @param DateTimeInterface|null $started_at The start date.
	 */
	public function start( ?DateTimeInterface $started_at = null ): void;

	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @param DateTimeInterface|null $completed_at The completion date.
	 */
	public function complete( ?DateTimeInterface $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Returns the lesson identifier.
	 *
	 * @return int
	 */
	public function get_lesson_id(): int;

	/**
	 * Returns the user identifier.
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Returns the lesson progress status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns the lesson start date.
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface;

	/**
	 * Returns the lesson completion date.
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface;

	/**
	 * Returns if the lesson progress is complete.
	 *
	 * @return bool
	 */
	public function is_complete(): bool;
}
