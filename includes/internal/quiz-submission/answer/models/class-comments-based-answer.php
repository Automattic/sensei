<?php
/**
 * File containing the Comments_Based_Answer class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Models;

use DateTimeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Answer.
 *
 * @internal
 *
 * @since $$next_version$$
 */
class Comments_Based_Answer extends Answer_Abstract {
	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param int               $submission_id The submission ID.
	 * @param int               $question_id   The question ID.
	 * @param string            $value         The answer value.
	 * @param DateTimeInterface $created_at    The created date.
	 * @param DateTimeInterface $updated_at    The updated date.
	 */
	public function __construct(
		int $submission_id,
		int $question_id,
		string $value,
		DateTimeInterface $created_at,
		DateTimeInterface $updated_at
	) {
		parent::__construct( 0, $submission_id, $question_id, $value, $created_at, $updated_at );
	}

	/**
	 * Get the answer ID.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Comments_Based_Answer does not have an ID.
	 */
	public function get_id(): int {
		throw new \BadMethodCallException( 'Comments_Based_Answer does not have an ID.' );
	}
}
