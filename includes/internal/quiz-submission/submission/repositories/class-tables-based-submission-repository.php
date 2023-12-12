<?php
/**
 * File containing the Tables_Based_Submission_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Submission_Repository.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Submission_Repository implements Submission_Repository_Interface {
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
	 * Creates a new quiz submission.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface {
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';

		$this->wpdb->insert(
			$this->get_table_name(),
			[
				'quiz_id'     => $quiz_id,
				'user_id'     => $user_id,
				'final_grade' => $final_grade,
				'created_at'  => $current_datetime->format( $date_format ),
				'updated_at'  => $current_datetime->format( $date_format ),
			],
			[
				'%d',
				'%d',
				is_null( $final_grade ) ? null : '%f',
				'%s',
				'%s',
			]
		);

		return new Tables_Based_Submission(
			$this->wpdb->insert_id,
			$quiz_id,
			$user_id,
			$final_grade,
			$current_datetime,
			$current_datetime
		);
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @internal
	 *
	 * @param int        $quiz_id     The quiz ID.
	 * @param int        $user_id     The user ID.
	 * @param float|null $final_grade The final grade.
	 *
	 * @return Submission_Interface The quiz submission.
	 */
	public function get_or_create( int $quiz_id, int $user_id, float $final_grade = null ): Submission_Interface {
		$submission = $this->get( $quiz_id, $user_id );

		if ( $submission ) {
			return $submission;
		}

		return $this->create( $quiz_id, $user_id, $final_grade );
	}

	/**
	 * Gets a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission_Interface|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission_Interface {
		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$this->get_table_name()} WHERE quiz_id = %d AND user_id = %d",
			$quiz_id,
			$user_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row( $query );

		if ( ! $row ) {
			return null;
		}

		return new Tables_Based_Submission(
			(int) $row->id,
			(int) $row->quiz_id,
			(int) $row->user_id,
			$row->final_grade,
			new DateTimeImmutable( $row->created_at, new DateTimeZone( 'UTC' ) ),
			new DateTimeImmutable( $row->updated_at, new DateTimeZone( 'UTC' ) )
		);
	}

	/**
	 * Get the questions related to the quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The quiz submission ID.
	 *
	 * @return array An array of question post IDs.
	 */
	public function get_question_ids( int $submission_id ): array {
		$quiz_answers_table = $this->wpdb->prefix . 'sensei_lms_quiz_answers';

		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT question_id FROM {$quiz_answers_table} WHERE submission_id = %d",
			$submission_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$question_ids = $this->wpdb->get_col( $query );

		return array_map( 'intval', $question_ids );
	}

	/**
	 * Save quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function save( Submission_Interface $submission ): void {
		$updated_at = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$submission->set_updated_at( $updated_at );

		$this->wpdb->update(
			$this->get_table_name(),
			[
				'final_grade' => $submission->get_final_grade(),
				'updated_at'  => $submission->get_updated_at()->format( 'Y-m-d H:i:s' ),
			],
			[
				'id' => $submission->get_id(),
			],
			[
				is_null( $submission->get_final_grade() ) ? null : '%f',
				'%s',
			],
			[
				'%d',
			]
		);
	}

	/**
	 * Delete the quiz submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The quiz submission.
	 */
	public function delete( Submission_Interface $submission ): void {
		$this->wpdb->delete(
			$this->get_table_name(),
			[
				'quiz_id' => $submission->get_quiz_id(),
				'user_id' => $submission->get_user_id(),
			],
			[
				'%d',
				'%d',
			]
		);
	}

	/**
	 * Get the quiz submission table name.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_submissions';
	}
}
