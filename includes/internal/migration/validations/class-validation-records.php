<?php
/**
 * File containing the Validation_Records class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Validations;

use Generator;
use RuntimeException;

/**
 * Enumerates persisted records by primary key in bounded batches.
 *
 * @internal
 * @since $$next-version$$
 */
class Validation_Records {
	private const BATCH_SIZE = 200;

	/**
	 * Get the comment records of a given activity type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $comment_type The Sensei activity comment type.
	 * @return Generator<int, object>
	 */
	public static function get_comments( string $comment_type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT comment_ID AS id, comment_post_ID AS post_id, user_id
			FROM %i
			WHERE comment_type = %s AND comment_ID > %d
			ORDER BY comment_ID
			LIMIT %d',
			array( $wpdb->comments, $comment_type ),
			$comment_type . ' comments'
		);
	}

	/**
	 * Get the HPPS progress records of a given type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $type The progress type.
	 * @return Generator<int, object>
	 */
	public static function get_progress_rows( string $type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT id, post_id, user_id
			FROM %i
			WHERE type = %s AND id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_progress', $type ),
			$type . ' progress table'
		);
	}

	/**
	 * Get the HPPS quiz submission records.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_submission_rows(): Generator {
		global $wpdb;

		return self::read(
			'SELECT id, quiz_id AS post_id, user_id
			FROM %i
			WHERE id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions' ),
			'quiz submission table'
		);
	}

	/**
	 * Get the HPPS answer rows whose submission no longer exists.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_answers_without_submissions(): Generator {
		global $wpdb;

		return self::read(
			'SELECT answers.id, answers.submission_id, answers.question_id
			FROM %i AS answers
			LEFT JOIN %i AS submissions ON submissions.id = answers.submission_id
			WHERE submissions.id IS NULL AND answers.id > %d
			ORDER BY answers.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_answers',
				$wpdb->prefix . 'sensei_lms_quiz_submissions',
			),
			'quiz answers table'
		);
	}

	/**
	 * Get the HPPS grade rows with a missing answer or a different question than the answer.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_grades_with_invalid_answers(): Generator {
		global $wpdb;

		return self::read(
			'SELECT grades.id, grades.answer_id, grades.question_id,
				answers.question_id AS answer_question_id, answers.submission_id, submissions.quiz_id, submissions.user_id
			FROM %i AS grades
			LEFT JOIN %i AS answers ON answers.id = grades.answer_id
			LEFT JOIN %i AS submissions ON submissions.id = answers.submission_id
			WHERE (answers.id IS NULL OR grades.question_id <> answers.question_id) AND grades.id > %d
			ORDER BY grades.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_grades',
				$wpdb->prefix . 'sensei_lms_quiz_answers',
				$wpdb->prefix . 'sensei_lms_quiz_submissions',
			),
			'quiz grades table'
		);
	}

	/**
	 * Read a query whose last two arguments are the primary key cursor and limit.
	 *
	 * @param string $query   The prepared statement template.
	 * @param array  $args    The arguments preceding the cursor and limit.
	 * @param string $source  The storage source being scanned.
	 * @return Generator<int, object>
	 * @throws RuntimeException If a batch cannot be read.
	 */
	private static function read( string $query, array $args, string $source ): Generator {
		global $wpdb;

		$last_id = 0;

		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Each batch reads the current stored records.
			$rows = $wpdb->get_results( $wpdb->prepare( $query, array_merge( $args, array( $last_id, self::BATCH_SIZE ) ) ) );

			if ( $wpdb->last_error ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The source is a fixed identifier.
				throw new RuntimeException( 'Could not read ' . $source . ' during validation.' );
			}

			$row_count = count( $rows );
			foreach ( $rows as $row ) {
				$last_id = (int) $row->id;
				yield $row;
			}
		} while ( self::BATCH_SIZE === $row_count );
	}
}
