<?php
/**
 * File containing the Answer class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer.
 *
 * @internal
 *
 * @since 4.7.2
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
	 * The answer value.
	 *
	 * @var string
	 */
	private $value;

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
	 * @param int               $id            The answer ID.
	 * @param int               $submission_id The submission ID.
	 * @param int               $question_id   The question ID.
	 * @param string            $value         The answer value.
	 * @param DateTimeInterface $created_at    The created date.
	 * @param DateTimeInterface $updated_at    The updated date.
	 */
	public function __construct(
		int $id,
		int $submission_id,
		int $question_id,
		string $value,
		DateTimeInterface $created_at,
		DateTimeInterface $updated_at
	) {
		$this->id            = $id;
		$this->submission_id = $submission_id;
		$this->question_id   = $question_id;
		$this->value         = $value;
		$this->created_at    = $created_at;
		$this->updated_at    = $updated_at;
	}

	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the submission ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_submission_id(): int {
		return $this->submission_id;
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
	 * Get the answer value.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public function get_value(): string {
		return $this->value;
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
