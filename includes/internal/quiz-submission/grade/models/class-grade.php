<?php
/**
 * File containing the Grade class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Grade {
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
	 * The grade feedback.
	 *
	 * @var string|null
	 */
	private $feedback;

	/**
	 * The created date.
	 *
	 * @var DateTimeInterface
	 */
	private $created_at;

	/**
	 * The updated date.
	 *
	 * @var DateTimeInterface
	 */
	private $updated_at;

	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param int               $id          The grade ID.
	 * @param int               $answer_id   The answer ID.
	 * @param int               $question_id The question ID.
	 * @param int               $points      The grade points.
	 * @param string|null       $feedback    The grade feedback.
	 * @param DateTimeInterface $created_at  The created data.
	 * @param DateTimeInterface $updated_at  The update date.
	 */
	public function __construct(
		int $id,
		int $answer_id,
		int $question_id,
		int $points,
		?string $feedback,
		DateTimeInterface $created_at,
		DateTimeInterface $updated_at
	) {
		$this->id          = $id;
		$this->answer_id   = $answer_id;
		$this->question_id = $question_id;
		$this->points      = $points;
		$this->feedback    = $feedback;
		$this->created_at  = $created_at;
		$this->updated_at  = $updated_at;
	}

	/**
	 * Get the grade ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_answer_id(): int {
		return $this->answer_id;
	}

	/**
	 * Get the question ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_question_id(): int {
		return $this->question_id;
	}

	/**
	 * Get the grade points.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_points(): int {
		return $this->points;
	}

	/**
	 * Get the grade feedback.
	 *
	 * @internal
	 *
	 * @return string|null
	 */
	public function get_feedback(): ?string {
		return $this->feedback;
	}

	/**
	 * Set the grade feedback.
	 *
	 * @internal
	 *
	 * @param string $feedback The feedback string.
	 */
	public function set_feedback( string $feedback ): void {
		$this->feedback = $feedback;
	}

	/**
	 * Get the created date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_created_at(): DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * Get the updated date.
	 *
	 * @internal
	 *
	 * @return DateTimeInterface
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->updated_at;
	}
}
