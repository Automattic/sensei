<?php
/**
 * File containing the class Tables_Based_Grade_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Models\Tables_Based_Grade;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use wpdb;

/**
 * Class Tables_Based_Grade_Repository
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Grade_Repository implements Grade_Repository_Interface {
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
	 * Creates a new grade.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission  The submission.
	 * @param Answer_Interface     $answer      The answer.
	 * @param int                  $question_id The question ID.
	 * @param int                  $points      The points.
	 * @param string|null          $feedback    The feedback.
	 *
	 * @return Grade_Interface The grade.
	 */
	public function create( Submission_Interface $submission, Answer_Interface $answer, int $question_id, int $points, ?string $feedback = null ): Grade_Interface {
		$current_date = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		$date_format  = 'Y-m-d H:i:s';

		$this->wpdb->insert(
			$this->get_table_name(),
			[
				'answer_id'   => $answer->get_id(),
				'question_id' => $question_id,
				'points'      => $points,
				'feedback'    => $feedback,
				'created_at'  => $current_date->format( $date_format ),
				'updated_at'  => $current_date->format( $date_format ),
			],
			[
				'%d',
				'%d',
				'%d',
				is_null( $feedback ) ? null : '%s',
				'%s',
				'%s',
			]
		);

		return new Tables_Based_Grade(
			$this->wpdb->insert_id,
			$answer->get_id(),
			$question_id,
			$points,
			$feedback,
			$current_date,
			$current_date
		);
	}

	/**
	 * Get all grades for a quiz submission.
	 *
	 * @internal
	 *
	 * @param int $submission_id The submission ID.
	 *
	 * @return Grade_Interface[] An array of grades.
	 */
	public function get_all( int $submission_id ): array {
		$answer_ids = $this->get_answer_ids_by_submission_id( $submission_id );
		if ( empty( $answer_ids ) ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $answer_ids ), '%d' ) );
		$grades_query = 'SELECT * FROM ' . $this->get_table_name() . ' WHERE answer_id IN (' . $placeholders . ')';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$grade_rows = $this->wpdb->get_results( $this->wpdb->prepare( $grades_query, ...$answer_ids ) );

		$grades = [];
		foreach ( $grade_rows as $grade_row ) {
			$grades[] = new Tables_Based_Grade(
				$grade_row->id,
				$grade_row->answer_id,
				$grade_row->question_id,
				$grade_row->points,
				$grade_row->feedback,
				new \DateTimeImmutable( $grade_row->created_at, new \DateTimeZone( 'UTC' ) ),
				new \DateTimeImmutable( $grade_row->updated_at, new \DateTimeZone( 'UTC' ) )
			);
		}

		return $grades;
	}

	/**
	 * Save multiple grades.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 * @param Grade_Interface[]    $grades     An array of grades.
	 */
	public function save_many( Submission_Interface $submission, array $grades ): void {
		foreach ( $grades as $grade ) {
			$this->save( $grade );
		}
	}

	/**
	 * Delete all grades for a submission.
	 *
	 * @internal
	 *
	 * @param Submission_Interface $submission The submission.
	 */
	public function delete_all( Submission_Interface $submission ): void {
		$answer_ids = $this->get_answer_ids_by_submission_id( $submission->get_id() );
		if ( empty( $answer_ids ) ) {
			return;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $answer_ids ), '%d' ) );
		$delete_query = 'DELETE FROM ' . $this->get_table_name() . ' WHERE answer_id IN (' . $placeholders . ')';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->wpdb->query( $this->wpdb->prepare( $delete_query, ...$answer_ids ) );
	}

	/**
	 * Save single grade.
	 *
	 * @param Grade_Interface $grade The grade.
	 */
	private function save( Grade_Interface $grade ): void {
		$updated_at = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

		$this->wpdb->update(
			$this->get_table_name(),
			[
				'points'     => $grade->get_points(),
				'feedback'   => $grade->get_feedback(),
				'updated_at' => $updated_at->format( 'Y-m-d H:i:s' ),
			],
			[
				'id' => $grade->get_id(),
			],
			[
				'%d',
				is_null( $grade->get_feedback() ) ? null : '%s',
				'%s',
			],
			[
				'%d',
			]
		);
	}

	/**
	 * Get all answer IDs for a submission.
	 *
	 * @param int $submission_id The submission ID.
	 */
	private function get_answer_ids_by_submission_id( int $submission_id ): array {
		$answers_query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT id FROM ' . $this->get_answers_table_name() . ' WHERE submission_id = %d',
			$submission_id
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_col( $answers_query );
	}

	/**
	 * Get the quiz grades table name.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_grades';
	}

	/**
	 * Get the quiz answers table name.
	 *
	 * @return string
	 */
	private function get_answers_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_quiz_answers';
	}
}
