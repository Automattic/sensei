<?php
/**
 * File containing the Answer_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for the answer models.
 *
 * @internal
 *
 * @since $$next_version$$
 */
interface Answer_Interface {
	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get the submission ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_submission_id(): int;

	/**
	 * Get the question ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_question_id(): int;

	/**
	 * Get the answer value.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public function get_value(): string;

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
}
