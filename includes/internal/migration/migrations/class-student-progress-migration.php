<?php
/**
 * File containing the class Student_Progress_Migration.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Sensei\Internal\Migration\Migration_Abstract;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

/**
 * Class Student_Progress_Migration.
 *
 * @since 4.16.1
 */
class Student_Progress_Migration extends Migration_Abstract {
	/**
	 * The name of the option that stores the last comment ID that was migrated.
	 *
	 * @var string
	 */
	public const LARST_COMMENT_ID_OPTION_NAME = 'sensei_migrated_progress_last_comment_id';

	/**
	 * The course progress data to insert.
	 *
	 * @var array
	 */
	private $progress_inserts = array();

	/**
	 * The size of a batch.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * The number of batches to insert in a single run.
	 *
	 * @var int
	 */
	private $batch_count;

	/**
	 * Constructs a new instance of the migration.
	 *
	 * @param int $batch_size The size of a batch (how many rows to insert in one insert query).
	 * @param int $batch_count The number of batches to insert in a single run.
	 */
	public function __construct( int $batch_size = 100, int $batch_count = 10 ) {
		$this->batch_size  = $batch_size;
		$this->batch_count = $batch_count;
	}

	/**
	 * Run the migration.
	 *
	 * @since 4.16.1
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 * @return int The number of rows inserted.
	 */
	public function run( bool $dry_run = true ) {
		$since_comment_id                                      = (int) get_option( self::LARST_COMMENT_ID_OPTION_NAME, 0 );
		[ $progress_comments, $mapped_meta, $last_comment_id ] = $this->get_comments_and_meta( $since_comment_id, $dry_run );

		if ( empty( $progress_comments ) ) {
			return 0;
		}

		if ( false === $last_comment_id && ! empty( $progress_comments ) ) {
			$this->add_error( __( 'Could not find the last comment ID migrating data.', 'sensei-lms' ) );
			return 0;
		}

		$this->prepare_progress_to_insert( $progress_comments, $mapped_meta );
		$inserted_rows = $this->insert_with_batches( $dry_run );

		update_option( self::LARST_COMMENT_ID_OPTION_NAME, $last_comment_id );

		return $inserted_rows;
	}

	/**
	 * Get the comments and comment meta to migrate.
	 *
	 * @param int  $after_comment_id The last comment ID that was migrated.
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 * @return array The comments and comment meta to migrate.
	 */
	private function get_comments_and_meta( int $after_comment_id, bool $dry_run ): array {
		global $wpdb;

		$limit = $this->batch_size * $this->batch_count;

		$comments_query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->comments} " .
				'WHERE comment_type IN (%s, %s) AND comment_ID > %d ' .
				'ORDER BY comment_ID ASC ' .
				'LIMIT %d',
			'sensei_course_status',
			'sensei_lesson_status',
			$after_comment_id,
			$limit
		);

		if ( $dry_run ) {
			echo esc_html( $comments_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$progress_comments = $wpdb->get_results( $comments_query );

		$comment_ids      = array();
		$post_ids         = array();
		$trashed_post_ids = array();
		foreach ( $progress_comments as $progress_comment ) {
			$comment_ids[] = $progress_comment->comment_ID;

			// At the moment we don't care about post meta for course progress.
			if ( 'sensei_lesson_status' === $progress_comment->comment_type ) {
				// Map the post ID to the comment ID. Is used later to map post meta to the comment ID.
				$post_ids[ $progress_comment->comment_post_ID ][] = $progress_comment->comment_ID;
			}
		}

		$progress_comment_meta = array();
		if ( ! empty( $comment_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$comment_meta_query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$wpdb->commentmeta} WHERE meta_key IN ( %s ) AND comment_id IN ( {$placeholders} )",
				'start',
				...$comment_ids
			);

			if ( $dry_run ) {
				echo esc_html( $comment_meta_query . "\n" );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$progress_comment_meta = $wpdb->get_results( $comment_meta_query );
		}

		$post_meta = array();
		if ( ! empty( $post_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			$ids          = array_keys( $post_ids );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$post_meta_query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$wpdb->postmeta} WHERE  meta_key IN ( %s, %s ) AND post_id IN ( {$placeholders} )",
				'_lesson_quiz',
				'_wp_trash_meta_comments_status',
				...$ids
			);
			if ( $dry_run ) {
				echo esc_html( $post_meta_query . "\n" );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$post_meta = $wpdb->get_results( $post_meta_query );
		}

		// Map different meta keys to the comment ID.
		$mapped_meta = [];

		// Map comment meta to the comment ID.
		foreach ( $progress_comment_meta as $meta ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$mapped_meta[ $meta->comment_id ][ $meta->meta_key ] = $meta->meta_value;
		}

		// Map post meta to the comment ID.
		foreach ( $post_meta as $meta ) {
			$comment_id = $post_ids[ $meta->post_id ];

			if ( '_wp_trash_meta_comments_status' === $meta->meta_key ) {
				$comment_statuses = maybe_unserialize( $meta->meta_value );
				if ( ! is_array( $comment_statuses ) ) {
					continue;
				}

				foreach ( $comment_statuses as $comment_id => $comment_status ) {
					$mapped_meta[ $comment_id ]['status'] = $comment_status;
				}
			} else {
				foreach ( $post_ids[ $meta->post_id ] as $comment_id ) {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$mapped_meta[ $comment_id ][ $meta->meta_key ] = $meta->meta_value;
				}
			}
		}

		$last_comment_id = end( $comment_ids );

		return array( $progress_comments, $mapped_meta, $last_comment_id );
	}


	/**
	 * Prepare the course progress data to be inserted.
	 *
	 * @param array $progress_comments The comments to migrate.
	 * @param array $mapped_meta The comment meta to migrate.
	 */
	private function prepare_progress_to_insert( $progress_comments, $mapped_meta ): void {
		$this->progress_inserts = array();
		foreach ( $progress_comments as $progress_comment ) {
			$meta = isset( $mapped_meta[ $progress_comment->comment_ID ] )
				? $mapped_meta[ $progress_comment->comment_ID ]
				: array();

			// Process comments for trashed posts.
			if ( 'post-trashed' === $progress_comment->comment_approved ) {
				$progress_comment->comment_approved = isset( $meta['status'] )
					? $meta['status']
					: 'in-progress';
			}

			switch ( $progress_comment->comment_type ) {
				case 'sensei_course_status':
					$this->prepare_course_progress_to_insert( $progress_comment, $meta );
					break;
				case 'sensei_lesson_status':
					$this->prepare_lesson_progress_to_insert( $progress_comment, $meta );
					break;
				default:
					$this->add_error(
						sprintf(
						/* translators: %s: comment type */
							__( 'Unknown comment (id %1$d) type: %2$s', 'sensei-lms' ),
							$progress_comment->comment_ID,
							$progress_comment->comment_type
						)
					);

			}
		}
	}

	/**
	 * Prepare the course progress data to be inserted.
	 *
	 * @param object $comment The comment to migrate.
	 * @param array  $meta The comment meta to migrate.
	 */
	private function prepare_course_progress_to_insert( $comment, $meta ): void {
		$course_status = 'in-progress';
		if ( Course_Progress_Interface::STATUS_COMPLETE === $comment->comment_approved ) {
			$course_status = 'complete';
		}

		if ( Course_Progress_Interface::STATUS_COMPLETE === $comment->comment_approved ) {
			$completed_at = $comment->comment_date_gmt;
		} else {
			$completed_at = null;
		}

		$started_at = 0;
		if ( isset( $meta['start'] ) ) {
			try {
				$started_at = $this->convert_to_utc( $meta['start'] );
			} catch ( \Exception $e ) {
				$this->add_error(
					sprintf(
					/* translators: %s: comment id */
						__( 'Unable to convert course progress start date (comment id %s) to UTC.', 'sensei-lms' ),
						$comment->comment_ID
					)
				);
			}
		}

		$this->progress_inserts[] = [
			'post_id'        => (int) $comment->comment_post_ID,
			'user_id'        => (int) $comment->user_id,
			'parent_post_id' => null,
			'type'           => 'course',
			'status'         => $course_status,
			'started_at'     => $started_at,
			'completed_at'   => $completed_at,
			'created_at'     => $comment->comment_date_gmt,
			'updated_at'     => $comment->comment_date_gmt,
		];
	}

	/**
	 * Prepare the lesson progress data to be inserted.
	 *
	 * @param object $comment The comment to migrate.
	 * @param array  $meta The comment meta to migrate.
	 */
	private function prepare_lesson_progress_to_insert( $comment, $meta ): void {
		$lesson_status = 'in-progress';
		$completed_at  = null;
		if ( in_array( $comment->comment_approved, [ 'complete', 'passed', 'graded' ], true ) ) {
			$completed_at  = $comment->comment_date_gmt;
			$lesson_status = 'complete';
		}

		if ( 'failed' === $comment->comment_approved ) {
			$quiz_id       = Sensei()->lesson->lesson_quizzes( $comment->comment_post_ID );
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
			if ( empty( $pass_required ) ) {
				// If pass is not required, we consider the lesson as complete.
				$completed_at  = $comment->comment_date_gmt;
				$lesson_status = 'complete';
			}
		}

		$started_at = 0;
		if ( isset( $meta['start'] ) ) {
			try {
				$started_at = $this->convert_to_utc( $meta['start'] );
			} catch ( \Exception $e ) {
				$this->add_error(
					sprintf(
					/* translators: %s: comment id */
						__( 'Unable to convert lesson progress start date (comment id %s) to UTC.', 'sensei-lms' ),
						$comment->comment_ID
					)
				);
			}
		}

		$this->progress_inserts[] = [
			'post_id'        => (int) $comment->comment_post_ID,
			'user_id'        => (int) $comment->user_id,
			'parent_post_id' => null,
			'type'           => 'lesson',
			'status'         => $lesson_status,
			'started_at'     => $started_at,
			'completed_at'   => $completed_at,
			'created_at'     => $comment->comment_date_gmt,
			'updated_at'     => $comment->comment_date_gmt,
		];

		// In case there is a quiz associated with the lesson,
		// we create a quiz progress entry as well.
		// We are able to determine a few non-initial statuses for the quiz.
		// Because of that, by default we set the status to in-progress.
		if ( isset( $meta['_lesson_quiz'] ) ) {
			$quiz_id            = $meta['_lesson_quiz'];
			$supported_statuses = [
				Quiz_Progress_Interface::STATUS_IN_PROGRESS,
				Quiz_Progress_Interface::STATUS_FAILED,
				Quiz_Progress_Interface::STATUS_GRADED,
				Quiz_Progress_Interface::STATUS_PASSED,
				Quiz_Progress_Interface::STATUS_UNGRADED,
				// We need to map lesson statuses to quiz' passed status.
				Lesson_Progress_Interface::STATUS_COMPLETE,
			];
			$quiz_status        = in_array( $comment->comment_approved, $supported_statuses, true )
				? $comment->comment_approved
				: Quiz_Progress_Interface::STATUS_IN_PROGRESS;
			$quiz_completed_at  = $completed_at;
			if ( Quiz_Progress_Interface::STATUS_IN_PROGRESS === $quiz_status ) {
				$quiz_completed_at = null;
			}
			if ( Lesson_Progress_Interface::STATUS_COMPLETE === $quiz_status ) {
				$quiz_status = Quiz_Progress_Interface::STATUS_PASSED;
			}

			$this->progress_inserts[] = [
				'post_id'        => (int) $quiz_id,
				'user_id'        => (int) $comment->user_id,
				'parent_post_id' => null,
				'type'           => 'quiz',
				'status'         => $quiz_status,
				'started_at'     => $started_at,
				'completed_at'   => $quiz_completed_at,
				'created_at'     => $comment->comment_date_gmt,
				'updated_at'     => $comment->comment_date_gmt,
			];
		}
	}

	/**
	 * Convert original date to UTC.
	 *
	 * @param string $date The date to convert.
	 */
	private function convert_to_utc( string $date ): string {
		$dt  = new \DateTime( $date, wp_timezone() );
		$utc = new \DateTimeZone( 'UTC' );
		$dt->setTimezone( $utc );
		return $dt->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Insert the progress data in batches.
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 */
	private function insert_with_batches( bool $dry_run ): int {
		$progress_count = count( $this->progress_inserts );
		$inserted_rows  = 0;
		for ( $i = 0; $i < $progress_count; $i += $this->batch_size ) {
			$batch        = array_slice( $this->progress_inserts, $i, $this->batch_size );
			$insert_query = $this->generate_insert_sql_for_batch( $batch );

			if ( $dry_run ) {
				echo esc_html( $insert_query . "\n" );
				continue;
			}

			$inserted_rows += $this->db_query( $insert_query );
		}

		return $inserted_rows;
	}

	/**
	 * Execute a database query.
	 *
	 * @param string $query The query to execute.
	 * @return int Number of rows affected.
	 */
	private function db_query( string $query ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $query );

		if ( '' !== $wpdb->last_error ) {
			$this->add_error( $wpdb->last_error );
		}

		return $result;
	}

	/**
	 * Generate SQL for data insertion.
	 *
	 * @param array $batch Data to generate queries for. Will be 'data' array returned by `$this->fetch_data_for_migration_for_ids()` method.
	 *
	 * @return string Generated queries for insertion for this batch, would be of the form:
	 * INSERT IGNORE INTO $table_name ($columns) values
	 *  ($value for row 1)
	 *  ($value for row 2)
	 * ...
	 */
	private function generate_insert_sql_for_batch( array $batch ): string {
		global $wpdb;
		$table      = $wpdb->prefix . 'sensei_lms_progress';
		$column_sql = '`post_id`,`user_id`,`parent_post_id`,`type`,`status`,`started_at`,`completed_at`,`created_at`,`updated_at`';
		$value_sql  = $this->generate_column_clauses( $batch );
		return "INSERT IGNORE INTO $table ($column_sql) VALUES $value_sql;"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, -- $insert_query is hardcoded, $value_sql is already escaped.
	}

	/**
	 * Generate values clauses to be used in INSERT statements.
	 *
	 * @param array $batch Actual data to migrate.
	 * @return string SQL clause for values.
	 */
	private function generate_column_clauses( array $batch ): string {
		global $wpdb;

		$column_placeholders = [
			'post_id'        => '%d',
			'user_id'        => '%d',
			'parent_post_id' => '%d',
			'type'           => '%s',
			'status'         => '%s',
			'started_at'     => '%s',
			'completed_at'   => '%s',
			'created_at'     => '%s',
			'updated_at'     => '%s',
		];

		$values = array();
		foreach ( array_values( $batch ) as $row ) {
			$row_values = array();
			foreach ( $column_placeholders as $column => $placeholder ) {
				if ( ! isset( $row[ $column ] ) || is_null( $row[ $column ] ) ) {
					$row_values[] = 'NULL';
				} else {
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $placeholder is a placeholder.
					$row_values[] = $wpdb->prepare( $placeholder, $row[ $column ] );
				}
			}

			$value_string = '(' . implode( ',', $row_values ) . ')';
			$values[]     = $value_string;
		}

		return implode( ',', $values );
	}
}
