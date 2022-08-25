<?php
/**
 * File containing the Grade_Tables class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade.
 *
 * @since $$next-version$$
 */
class Grade_Tables implements Grade_Interface {
	/**
	 * The grade ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The answer ID.
	 *
	 * @var int
	 */
	private $answer_id;

	/**
	 * The question ID.
	 *
	 * @var int
	 */
	private $question_id;

	/**
	 * The grade points.
	 *
	 * @var int
	 */
	private $points;

	/**
	 * The created date.
	 *
	 * @var DateTime
	 */
	private $created_at;

	/**
	 * The updated date.
	 *
	 * @var DateTime|null
	 */
	private $updated_at;

	/**
	 * The grade feedback.
	 *
	 * @var string|null
	 */
	private $feedback;

	/**
	 * Constructor.
	 *
	 * @param int           $id          The grade ID.
	 * @param int           $answer_id   The answer ID.
	 * @param int           $question_id The question ID.
	 * @param int           $points      The grade points.
	 * @param DateTime      $created_at  The created date.
	 * @param DateTime|null $updated_at  The updated date.
	 * @param string|null   $feedback    The grade feedback.
	 */
	public function __construct(
		int $id,
		int $answer_id,
		int $question_id,
		int $points,
		DateTime $created_at,
		DateTime $updated_at = null,
		string $feedback = null
	) {
		$this->id          = $id;
		$this->answer_id   = $answer_id;
		$this->question_id = $question_id;
		$this->points      = $points;
		$this->feedback    = $feedback;
		$this->created_at  = $created_at;
		$this->updated_at  = $updated_at ?? $created_at;
	}

	/**
	 * Get the grade ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the answer ID.
	 *
	 * @return int
	 */
	public function get_answer_id(): int {
		return $this->answer_id;
	}

	/**
	 * Get the question ID.
	 *
	 * @return int
	 */
	public function get_question_id(): int {
		return $this->question_id;
	}

	/**
	 * Get the grade points.
	 *
	 * @return int
	 */
	public function get_points(): int {
		return $this->points;
	}

	/**
	 * Get the grade feedback.
	 *
	 * @return int
	 */
	public function get_feedback(): int {
		return $this->feedback;
	}

	/**
	 * Get the created date.
	 *
	 * @return DateTime
	 */
	public function get_created_at(): DateTime {
		return $this->created_at;
	}

	/**
	 * Get the updated date.
	 *
	 * @return DateTime
	 */
	public function get_updated_at(): DateTime {
		return $this->updated_at;
	}
}
