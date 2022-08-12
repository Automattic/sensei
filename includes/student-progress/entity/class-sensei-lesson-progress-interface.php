<?php
/**
 * File containing the Sensei_Lesson_Progress_Interface.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Progress.
 *
 * @since $$next-version$$
 */
interface Sensei_Lesson_Progress_Interface {
	/**
	 * Status lesson in progress.
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Status lesson completed.
	 */
	public const STATUS_COMPLETE = 'complete';

	/**
	 * Changes the lesson progress status and start date.
	 *
	 * @param DateTime|null $started_at The start date.
	 */
	public function start( ?DateTime $started_at = null ): void;

	/**
	 * Changes the lesson progress status and end date.
	 *
	 * @param DateTime|null $completed_at The completion date.
	 */
	public function complete( ?DateTime $completed_at = null ): void;

	/**
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * @return int
	 */
	public function get_lesson_id(): int;

	/**
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * @return int
	 */
	public function get_course_id(): int;

	/**
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * @return DateTime|null
	 */
	public function get_started_at(): ?DateTime;

	/**
	 * @return DateTime|null
	 */
	public function get_completed_at(): ?DateTime;

	/**
	 * Changes the lesson progress status and end date.
	 *
	 * @param array $metadata The lesson progress metadata.
	 */
	public function get_metadata(): array;
}
