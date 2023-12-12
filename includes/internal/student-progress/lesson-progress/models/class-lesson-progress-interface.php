<?php
/**
 * File containing the Lesson_Progress_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Lesson_Progress_Interface.
 *
 * @internal
 *
 * @since 4.18.0
 */
interface Lesson_Progress_Interface {
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
	 * Changes the lesson progress status and start date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at The start date.
	 */
	public function start( ?DateTimeInterface $started_at = null ): void;

	/**
	 * Changes the lesson progress status and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $completed_at The completion date.
	 */
	public function complete( ?DateTimeInterface $completed_at = null ): void;

	/**
	 * Returns the progress identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Returns the lesson identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_lesson_id(): int;

	/**
	 * Returns the user identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Returns the lesson progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns the lesson start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface;

	/**
	 * Returns the lesson completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface;

	/**
	 * Returns if the lesson progress is complete.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function is_complete(): bool;

	/**
	 * Returns the lesson progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface;

	/**
	 * Returns the lesson progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface;

	/**
	 * Set lesson progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at The updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void;
}
