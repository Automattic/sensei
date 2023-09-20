<?php
/**
 * File containing the Comments_Based_Grade class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Grade.
 *
 * @internal
 *
 * @since $$next_version$$
 */
class Comments_Based_Grade extends Grade_Abstract {
	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param int               $question_id The question ID.
	 * @param int               $points      The grade points.
	 * @param string|null       $feedback    The grade feedback.
	 * @param DateTimeInterface $created_at  The created data.
	 * @param DateTimeInterface $updated_at  The update date.
	 */
	public function __construct(
		int $question_id,
		int $points,
		?string $feedback,
		DateTimeInterface $created_at,
		DateTimeInterface $updated_at
	) {
		parent::__construct( 0, 0, $question_id, $points, $feedback, $created_at, $updated_at );
	}

	/**
	 * Get the grade ID.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Comments_Based_Grade does not have an ID.
	 */
	public function get_id(): int {
		throw new \BadMethodCallException( 'Comments_Based_Grade does not have an ID.' );
	}

	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Comments_Based_Grade does not have an answer ID.
	 */
	public function get_answer_id(): int {
		throw new \BadMethodCallException( 'Comments_Based_Grade does not have an answer ID.' );
	}
}
