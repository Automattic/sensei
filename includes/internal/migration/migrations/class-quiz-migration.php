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
 * @since $$next-version$$
 */
class Quiz_Migration extends Migration_Abstract {
	/**
	 * Migration errors option name.
	 *
	 * @var string
	 */
	public const LAST_COMMENT_ID_OPTION_NAME = 'sensei_migrated_quiz_last_comment_id';

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
	 * The targeted plugin version.
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	public function target_version(): string {
		return '1.0.0';
	}

	/**
	 * Run the migration.
	 *
	 * @since $$next-version$$
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 * @return int The number of quiz submissions migrated.
	 */
	public function run( bool $dry_run = true ) {
		$since_comment_id = (int) get_option( self::LAST_COMMENT_ID_OPTION_NAME, 0 );

		global $wpdb;
		$comments_query = $wpdb->prepare(
			"SELECT * FROM $wpdb->comments AS comments
			INNER JOIN $wpdb->commentmeta AS meta ON comments.comment_ID = meta.comment_id
			WHERE comments.comment_type = 'sensei_lesson_status'
			AND comments.comment_ID > %d
			AND meta.meta_key = 'quiz_answers'
			ORDER BY comments.comment_ID
			LIMIT %d",
			$since_comment_id,
			$this->batch_size
		);

		if ( $dry_run ) {
			echo esc_html( $comments_query . "\n" );
		}

		$comments = $wpdb->get_results( $comments_query );
		if ( ! $comments ) {
			return 0;
		}

		$comment_ids     = wp_list_pluck( $comments, 'comment_ID' );
		$quiz_meta_query = $wpdb->prepare(
			"SELECT * FROM
			$wpdb->commentmeta WHERE meta_key IN ( %s, %s, %s, %s )
			AND comment_id IN ( " . implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) ) . ' )',
			'quiz_answers',
			'quiz_grades',
			'quiz_answers_feedback',
			'grade',
			...$comment_ids
		);

		if ( $dry_run ) {
			echo esc_html( $quiz_meta_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$quiz_meta = $wpdb->get_results( $quiz_meta_query );
		$quiz_data = [];
		foreach ( $quiz_meta as $meta ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- We're not doing a meta query here, phpcs is confused.
			$quiz_data[ $meta->comment_id ][ $meta->meta_key ] = $meta->meta_value;
		}

		foreach ( $comments as $comment ) {
			$quiz_id = Sensei()->lesson->lesson_quizzes( $comment->comment_post_ID );
			if ( ! $quiz_id ) {
				continue;
			}

			$final_grade = isset( $quiz_data[ $comment->comment_ID ]['grade'] )
				? (float) $quiz_data[ $comment->comment_ID ]['grade']
				: null;

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

			if ( false === $success ) {
				$this->add_error( $wpdb->last_error );
				continue;
			}

			$quiz_answers = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_answers'] )
				? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_answers'] )
				: array();
			if ( ! $quiz_answers ) {
				continue;
			}

			$quiz_grades = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_grades'] )
				? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_grades'] )
				: array();

			$quiz_answers_feedback = ! empty( $quiz_data[ $comment->comment_ID ]['quiz_answers_feedback'] )
				? maybe_unserialize( $quiz_data[ $comment->comment_ID ]['quiz_answers_feedback'] )
				: array();

			$submission_id       = $wpdb->insert_id;
			$question_answer_map = [];

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

				if ( false === $success ) {
					$this->add_error( $wpdb->last_error );
					continue;
				}

				$answer_id                           = $wpdb->insert_id;
				$question_answer_map[ $question_id ] = $answer_id;
			}

			foreach ( $quiz_grades as $question_id => $points ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$success = (bool) $wpdb->insert(
					$wpdb->prefix . 'sensei_lms_quiz_grades',
					[
						'answer_id'   => $question_answer_map[ $question_id ],
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

				if ( false === $success ) {
					$this->add_error( $wpdb->last_error );
				}
			}
		}

		$last_comment_id = end( $comment_ids );
		update_option( self::LAST_COMMENT_ID_OPTION_NAME, $last_comment_id );

		return count( $comment_ids );
	}
}
