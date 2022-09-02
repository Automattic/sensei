<?php
/**
 * File containing the Submission class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Submission\Models;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission.
 *
 * @since $$next-version$$
 */
class Submission {
	/**
	 * The submission ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The quiz post ID.
	 *
	 * @var int
	 */
	private $quiz_id;

	/**
	 * The user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * The final grade in percentage.
	 *
	 * @var float|null
	 */
	private $final_grade;

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
	 * @param int           $id          The submission ID.
	 * @param int           $quiz_id     The quiz post ID.
	 * @param int           $user_id     The user ID.
	 * @param DateTime|null $created_at  The created date.
	 * @param DateTime|null $updated_at  The updated date.
	 * @param float|null    $final_grade The final grade (%).
	 */
	public function __construct(
		int $id,
		int $quiz_id,
		int $user_id,
		DateTime $created_at,
		DateTime $updated_at = null,
		float $final_grade = null
	) {
		$this->id          = $id;
		$this->quiz_id     = $quiz_id;
		$this->user_id     = $user_id;
		$this->final_grade = $final_grade;
		$this->created_at  = $created_at;
		$this->updated_at  = $updated_at ?? $created_at;
	}

	/**
	 * Get the submission ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the quiz ID.
	 *
	 * @return int
	 */
	public function get_quiz_id(): int {
		return $this->quiz_id;
	}

	/**
	 * Get the user ID.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Get the final grade.
	 *
	 * @return float|null
	 */
	public function get_final_grade(): ?float {
		return $this->final_grade;
	}

	/**
	 * Set the final grade.
	 *
	 * @param float $final_grade The final grade.
	 */
	public function set_final_grade( float $final_grade ): void {
		$this->final_grade = $final_grade;
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
