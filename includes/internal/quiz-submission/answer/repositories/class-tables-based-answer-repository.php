<?php
/**
 * File containing the Tables_Based_Answer_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Models\Tables_Based_Answer;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Answer_Repository.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Answer_Repository implements Answer_Repository_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Create a new answer.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission.
	 * @param int                  $question_id The question ID.
	 * @param string               $value       The answer value.
	 *
	 * @return Answer_Interface The answer model.
	 */
	public function create( Submission_Interface $submission, int $question_id, string $value ): Answer_Interface {
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';

		$this->wpdb->insert(
			$this->get_table_name(),
			[
				'submission_id' => $submission->get_id(),
				'question_id'   => $question_id,
				'value'         => $value,
				'created_at'    => $current_datetime->format( $date_format ),
				'updated_at'    => $current_datetime->format( $date_format ),
			],
			[
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);

		return new Tables_Based_Answer(
			$this->wpdb->insert_id,
			$submission->get_id(),
			$question_id,
			$value,
			$current_datetime,
			$current_datetime
		);
	}

	/**
	 * Get all answers for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Answer_Interface[] An array of answers.
	 */
	public function get_all( int $submission_id ): array {
		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$this->get_table_name()} WHERE submission_id = %d",
			$submission_id
		);

		$answers = [];
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared earlier.
		foreach ( $this->wpdb->get_results( $query ) as $result ) {
			$answers[] = new Tables_Based_Answer(
				$result->id,
				$result->submission_id,
				$result->question_id,
				$result->value,
				new DateTimeImmutable( $result->created_at, new DateTimeZone( 'UTC' ) ),
				new DateTimeImmutable( $result->updated_at, new DateTimeZone( 'UTC' ) )
			);
		}

		return $answers;
	}

	/**
	 * Delete all answers for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void {
		$this->wpdb->delete(
			$this->get_table_name(),
			[
				'submission_id' => $submission->get_id(),
			],
			[
				'%d',
			]
		);
	}

	/**
	 * Get the quiz answers table name.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_answers';
	}
}
