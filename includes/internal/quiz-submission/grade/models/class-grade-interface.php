<?php
/**
 * File containing the Grade_Interface.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for the grade models.
 *
 * @internal
 *
 * @since $$next_version$$
 */
interface Grade_Interface {
	/**
	 * Get the grade ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_answer_id(): int;

	/**
	 * Get the question ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_question_id(): int;

	/**
	 * Get the grade points.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_points(): int;

	/**
	 * Get the grade feedback.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_feedback(): ?string;

	/**
	 * Set the grade feedback.
	 *
	 * @internal
	 *
	 * @param string $feedback The feedback string.
	 */
	public function set_feedback( string $feedback ): void;

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
