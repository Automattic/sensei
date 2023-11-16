<?php
/**
 * File containing the Quiz_Progress_Interface class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Quiz_Progress_Interface.
 *
 * @internal
 *
 * @since 4.18.0
 */
interface Quiz_Progress_Interface {
	/**
	 * In progress quiz status.
	 *
	 * @internal
	 */
	public const STATUS_IN_PROGRESS = 'in-progress';

	/**
	 * Passed quiz status.
	 *
	 * @internal
	 */
	public const STATUS_PASSED = 'passed';

	/**
	 * Graded quiz status.
	 *
	 * @internal
	 */
	public const STATUS_GRADED = 'graded';

	/**
	 * Ungraded quiz status.
	 *
	 * @internal
	 */
	public const STATUS_UNGRADED = 'ungraded';

	/**
	 * Failed quiz status.
	 *
	 * @internal
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * Set the status of the quiz to 'in-progress' and start date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $started_at Quiz start date.
	 */
	public function start( ?DateTimeInterface $started_at = null ): void;

	/**
	 * Set the status of the quiz to 'passed' and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $passed_at Quiz completion date.
	 */
	public function pass( ?DateTimeInterface $passed_at = null ): void;

	/**
	 * Set the status of the quiz to 'graded' and completion date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface|null $graded_at Quiz completion date.
	 */
	public function grade( ?DateTimeInterface $graded_at = null ): void;

	/**
	 * Set the status of the quiz to 'ungraded' and reset completion date.
	 *
	 * @internal
	 */
	public function ungrade(): void;

	/**
	 * Set the status of the quiz to 'failed' and reset completion date.
	 *
	 * @internal
	 */
	public function fail(): void;

	/**
	 * Get the progress identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get the quiz identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_quiz_id(): int;

	/**
	 * Get the user identifier.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Get the progress status.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_status(): ?string;

	/**
	 * Returns whether the quiz is submitted.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function is_quiz_submitted(): bool;

	/**
	 * Returns whether the quiz is completed.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function is_quiz_completed(): bool;

	/**
	 * Get the quiz start date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_started_at(): ?DateTimeInterface;

	/**
	 * Get the quiz completion date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface|null
	 */
	public function get_completed_at(): ?DateTimeInterface;

	/**
	 * Get the quiz progress created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface;

	/**
	 * Get the quiz progress updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface;

	/**
	 * Set the quiz progress updated date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at Quiz progress updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void;
}
