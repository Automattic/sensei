<?php
/**
 * File containing the class Quiz_Migration.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Sensei\Internal\Migration\Migration_Abstract;

/**
 * Class Quiz_Migration.
 *
 * @internal
 *
 * @since 4.17.0
 */
class Quiz_Migration extends Migration_Abstract {
	/**
	 * Migration errors option name.
	 *
	 * @var string
	 */
	public const LAST_COMMENT_ID_OPTION_NAME = 'sensei_migrated_quiz_last_comment_id';

	/**
	 * Whether to run the migration in dry-run mode.
	 *
	 * @var bool
	 */
	private $dry_run = true;

	/**
	 * The last insert query.
	 *
	 * @var string
	 */
	private $last_insert_query = '';

	/**
	 * The size of a batch or how many quiz submissions to migrate in a single run.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * Constructs a new instance of the migration.
	 *
	 * @param int $batch_size The size of a batch or how many quiz submissions to migrate in a single run.
	 */
	public function __construct( int $batch_size = 100 ) {
		$this->batch_size = $batch_size;
	}

	/**
	 * Run the migration.
	 *
	 * @since 4.17.0
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 * @return int The number of quiz submissions migrated.
	 */
	public function run( bool $dry_run = true ) {
		$this->dry_run = $dry_run;

		$comments = $this->get_comments();
		if ( ! $comments ) {
			return 0;
		}

		if ( $this->dry_run ) {
			$this->stop_insert_query_execution();
		}

		$quiz_data = $this->get_quiz_data( $comments );
		foreach ( $comments as $comment ) {
			$submission_id = $this->insert_quiz_submission( $comment, $quiz_data );
			if ( ! $submission_id ) {
				continue;
			}

			$answer_ids = $this->insert_quiz_answers( $comment, $quiz_data, $submission_id );
			$this->insert_quiz_grades( $comment, $quiz_data, $answer_ids );
		}

		if ( $this->dry_run ) {
			$this->enable_insert_query_execution();
		}

		$last_comment_id = end( $comments )->comment_ID;
		update_option( self::LAST_COMMENT_ID_OPTION_NAME, $last_comment_id );

		return count( $comments );
	}

	/**
	 * Get the lesson progress comments.
	 *
	 * @return array
	 */
	private function get_comments(): array {
		global $wpdb;

		$since_comment_id = (int) get_option( self::LAST_COMMENT_ID_OPTION_NAME, 0 );
		$comments_query   = $wpdb->prepare(
			"SELECT comments.* FROM $wpdb->comments AS comments
			INNER JOIN $wpdb->commentmeta AS meta ON comments.comment_ID = meta.comment_id
			WHERE comments.comment_type = 'sensei_lesson_status'
			AND comments.comment_ID > %d
			AND meta.meta_key = 'quiz_answers'
			ORDER BY comments.comment_ID
			LIMIT %d",
			$since_comment_id,
			$this->batch_size
		);

		if ( $this->dry_run ) {
			echo esc_html( $comments_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $comments_query );
	}

	/**
	 * Get the quiz metadata. This includes the quiz answers, grades and feedback.
	 *
	 * @param array $comments The lesson progress comments.
	 *
	 * @return array
	 */
	private function get_quiz_data( array $comments ): array {
		global $wpdb;

		$comment_ids     = wp_list_pluck( $comments, 'comment_ID' );
		$quiz_meta_query = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			"SELECT * FROM
			$wpdb->commentmeta WHERE meta_key IN ( %s, %s, %s, %s )
			AND comment_id IN ( " . implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) ) . ' )',
			'quiz_answers',
			'quiz_grades',
			'quiz_answers_feedback',
			'grade',
			...$comment_ids
		);

		if ( $this->dry_run ) {
			echo esc_html( $quiz_meta_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$quiz_meta = $wpdb->get_results( $quiz_meta_query );
		$quiz_data = [];
		foreach ( $quiz_meta as $meta ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- We're not doing a meta query here, phpcs is confused.
			$quiz_data[ $meta->comment_id ][ $meta->meta_key ] = $meta->meta_value;
		}

		return $quiz_data;
	}

	/**
	 * Insert the quiz submission.
	 *
	 * @param object $comment The comment object.
	 * @param array  $quiz_data The quiz data.
	 *
	 * @return int|null
	 */
	private function insert_quiz_submission( object $comment, array $quiz_data ): ?int {
		$quiz_id = Sensei()->lesson->lesson_quizzes( $comment->comment_post_ID );
		if ( ! $quiz_id ) {
			return null;
		}

		$final_grade = isset( $quiz_data[ $comment->comment_ID ]['grade'] )
			? (float) $quiz_data[ $comment->comment_ID ]['grade']
			: null;

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$success = (bool) $wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_submissions',
			[
				'quiz_id'     => $quiz_id,
				'user_id'     => $comment->user_id,
				'final_grade' => $final_grade,
				'created_at'  => $comment->comment_date_gmt,
				'updated_at'  => $comment->comment_date_gmt,
			],
			[
				'%d',
				'%d',
				is_null( $final_grade ) ? null : '%f',
				'%s',
				'%s',
			]
		);

		if ( $this->dry_run ) {
			echo esc_html( $this->last_insert_query . "\n" );
			return 1;
		}

		if ( false === $success ) {
			$this->add_error( $wpdb->last_error );
			return null;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Insert the quiz answers.
	 *
	 * @param object $comment The comment object.
	 * @param array  $quiz_data The quiz metadata.
	 * @param int    $submission_id The quiz submission id.
	 *
	 * @return array
	 */
	private function insert_quiz_answers( object $comment, array $quiz_data, int $submission_id ): array {
		$quiz_answers = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_answers'] )
			? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_answers'] )
			: array();
		if ( ! $quiz_answers ) {
			$this->add_error(
				sprintf(
					/* translators: %s: comment id */
					__( 'No quiz answers found for comment ID %d', 'sensei-lms' ),
					$comment->comment_ID
				)
			);

			return array();
		}

		global $wpdb;

		$answer_ids = array();
		foreach ( $quiz_answers as $question_id => $value ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$success = (bool) $wpdb->insert(
				$wpdb->prefix . 'sensei_lms_quiz_answers',
				[
					'submission_id' => $submission_id,
					'question_id'   => $question_id,
					'value'         => $value,
					'created_at'    => $comment->comment_date_gmt,
					'updated_at'    => $comment->comment_date_gmt,
				],
				[
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				]
			);

			if ( $this->dry_run ) {
				echo esc_html( $this->last_insert_query . "\n" );
				$answer_ids[ $question_id ] = 1;
				continue;
			}

			if ( false === $success ) {
				$this->add_error( $wpdb->last_error );
				continue;
			}

			$answer_ids[ $question_id ] = $wpdb->insert_id;
		}

		return $answer_ids;
	}

	/**
	 * Insert the quiz grades.
	 *
	 * @param object $comment The comment object.
	 * @param array  $quiz_data The quiz metadata.
	 * @param array  $answer_ids The answer ids.
	 *
	 * @return array
	 */
	private function insert_quiz_grades( object $comment, array $quiz_data, array $answer_ids ): array {
		$quiz_grades = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_grades'] )
			? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_grades'] )
			: array();

		if ( ! $quiz_grades ) {
			return array();
		}

		$quiz_answers_feedback = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_answers_feedback'] )
			? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_answers_feedback'] )
			: array();

		global $wpdb;

		$grade_ids = array();
		foreach ( $quiz_grades as $question_id => $points ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$success = (bool) $wpdb->insert(
				$wpdb->prefix . 'sensei_lms_quiz_grades',
				[
					'answer_id'   => $answer_ids[ $question_id ],
					'question_id' => $question_id,
					'points'      => $points,
					'feedback'    => $quiz_answers_feedback[ $question_id ] ?? null,
					'created_at'  => $comment->comment_date_gmt,
					'updated_at'  => $comment->comment_date_gmt,
				],
				[
					'%d',
					'%d',
					'%d',
					isset( $quiz_answers_feedback[ $question_id ] ) ? '%s' : null,
					'%s',
					'%s',
				]
			);

			if ( $this->dry_run ) {
				echo esc_html( $this->last_insert_query . "\n" );
				continue;
			}

			if ( false === $success ) {
				$this->add_error( $wpdb->last_error );
			}

			$grade_ids[ $question_id ] = $wpdb->insert_id;
		}

		return $grade_ids;
	}

	/**
	 * Stop the execution of WPDB INSERT queries.
	 */
	private function stop_insert_query_execution(): void {
		add_filter( 'query', [ $this, 'stop_insert_query_execution_hook' ] );
	}

	/**
	 * Enable the execution of WPDB INSERT queries.
	 */
	private function enable_insert_query_execution(): void {
		remove_filter( 'query', [ $this, 'stop_insert_query_execution_hook' ] );
	}

	/**
	 * The hook that stops the execution of WPDB INSERT queries.
	 *
	 * @param string $query The database query.
	 *
	 * @return string
	 */
	public function stop_insert_query_execution_hook( $query ) {
		if ( str_starts_with( $query, 'INSERT' ) ) {
			$this->last_insert_query = $query;
			return '';
		}

		return $query;
	}
}
