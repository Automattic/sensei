<?php
/**
 * File containing the Submission class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission.
 *
 * @internal
 *
 * @since 4.7.2
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
	 * @param int               $id          The submission ID.
	 * @param int               $quiz_id     The quiz post ID.
	 * @param int               $user_id     The user ID.
	 * @param float|null        $final_grade The final grade (%).
	 * @param DateTimeInterface $created_at  The created date.
	 * @param DateTimeInterface $updated_at  The updated date.
	 */
	public function __construct(
		int $id,
		int $quiz_id,
		int $user_id,
		?float $final_grade,
		DateTimeInterface $created_at,
		DateTimeInterface $updated_at
	) {
		$this->id          = $id;
		$this->quiz_id     = $quiz_id;
		$this->user_id     = $user_id;
		$this->final_grade = $final_grade;
		$this->created_at  = $created_at;
		$this->updated_at  = $updated_at;
	}

	/**
	 * Get the submission ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the quiz ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_quiz_id(): int {
		return $this->quiz_id;
	}

	/**
	 * Get the user ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Get the final grade.
	 *
	 * @internal
	 *
	 * @return float|null
	 */
	public function get_final_grade(): ?float {
		return $this->final_grade;
	}

	/**
	 * Set the final grade.
	 *
	 * @internal
	 *
	 * @param float|null $final_grade The final grade.
	 */
	public function set_final_grade( ?float $final_grade ): void {
		$this->final_grade = $final_grade;
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
