<?php
/**
 * File containing the Answer class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer.
 *
 * @since $$next-version$$
 */
class Answer {
	/**
	 * The answer ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The submission ID.
	 *
	 * @var int
	 */
	private $submission_id;

	/**
	 * The question ID.
	 *
	 * @var int
	 */
	private $question_id;

	/**
	 * The question answer.
	 *
	 * @var string
	 */
	private $answer;

	/**
	 * The created date.
	 *
	 * @var DateTime
	 */
	private $created_at;

	/**
	 * The updated date.
	 *
	 * @var DateTime
	 */
	private $updated_at;

	/**
	 * Constructor.
	 *
	 * @param int           $id            The answer ID.
	 * @param int           $submission_id The submission ID.
	 * @param int           $question_id   The question ID.
	 * @param string        $answer        The question answer.
	 * @param DateTime      $created_at    The created date.
	 * @param DateTime|null $updated_at    The updated date.
	 */
	public function __construct(
		int $id,
		int $submission_id,
		int $question_id,
		string $answer,
		DateTime $created_at,
		DateTime $updated_at = null
	) {
		$this->id            = $id;
		$this->submission_id = $submission_id;
		$this->question_id   = $question_id;
		$this->answer        = $answer;
		$this->created_at    = $created_at;
		$this->updated_at    = $updated_at ?? $created_at;
	}

	/**
	 * Get the answer ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the submission ID.
	 *
	 * @return int
	 */
	public function get_submission_id(): int {
		return $this->submission_id;
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
	 * Get the question answer.
	 *
	 * @return string
	 */
	public function get_answer(): string {
		return $this->answer;
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
