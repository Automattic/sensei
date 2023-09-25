<?php
/**
 * File containing the Submission_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for the submission.
 *
 * @internal
 *
 * @since $$next_version$$
 */
interface Submission_Interface {
	/**
	 * Get the submission ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get the quiz ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_quiz_id(): int;

	/**
	 * Get the user ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Get the final grade.
	 *
	 * @internal
	 *
	 * @return float|null
	 */
	public function get_final_grade(): ?float;

	/**
	 * Set the final grade.
	 *
	 * @internal
	 *
	 * @param float|null $final_grade The final grade.
	 */
	public function set_final_grade( ?float $final_grade ): void;

	/**
	 * Get the created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface;

	/**
	 * Get the updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface;

	/**
	 * Set the updated at date.
	 *
	 * @internal
	 *
	 * @param DateTimeInterface $updated_at The updated date.
	 */
	public function set_updated_at( DateTimeInterface $updated_at ): void;
}
