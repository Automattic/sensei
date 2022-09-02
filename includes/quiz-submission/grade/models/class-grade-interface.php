<?php
/**
 * File containing the Grade_Interface.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Grade\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Grade_Interface.
 *
 * @since $$next-version$$
 */
interface Grade_Interface {
	/**
	 * Get the grade ID.
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get the answer ID.
	 *
	 * @return int
	 */
	public function get_answer_id(): int;

	/**
	 * Get the question ID.
	 *
	 * @return int
	 */
	public function get_question_id(): int;

	/**
	 * Get the grade points.
	 *
	 * @return int
	 */
	public function get_points(): int;

	/**
	 * Get the grade feedback.
	 *
	 * @return string|null
	 */
	public function get_feedback(): ?string;

	/**
	 * Set the grade feedback.
	 *
	 * @param string $feedback The feedback string.
	 */
	public function set_feedback( string $feedback ): void;

	/**
	 * Get the created date.
	 *
	 * @return DateTime
	 */
	public function get_created_at(): DateTime;

	/**
	 * Get the updated date.
	 *
	 * @return DateTime
	 */
	public function get_updated_at(): DateTime;
}
