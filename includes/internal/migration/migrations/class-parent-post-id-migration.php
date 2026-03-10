<?php
/**
 * File containing the class Parent_Post_Id_Migration.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Sensei\Internal\Migration\Migration_Abstract;

/**
 * Class Parent_Post_Id_Migration.
 *
 * Backfills NULL parent_post_id values in sensei_lms_progress for sites
 * that already have HPPS enabled. Lesson progress gets the course ID,
 * quiz progress gets the lesson ID, course progress stays NULL.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Parent_Post_Id_Migration extends Migration_Abstract {
	/**
	 * The name of the option that stores the last progress ID that was migrated.
	 *
	 * @var string
	 */
	public const LAST_ID_OPTION_NAME = 'sensei_migrated_parent_post_id_last_id';

	/**
	 * The name of the option that stores whether lesson backfill is complete.
	 *
	 * @var string
	 */
	public const LESSONS_COMPLETE_OPTION_NAME = 'sensei_migrated_parent_post_id_lessons_complete';

	/**
	 * The number of rows to process in a single run.
	 *
	 * @var int
	 */
	private $batch_size;

	/**
	 * Constructs a new instance of the migration.
	 *
	 * @param int $batch_size The number of rows to process in a single run.
	 */
	public function __construct( int $batch_size = 250 ) {
		/**
		 * Filter the batch size for parent_post_id backfill migration.
		 *
		 * @since $$next-version$$
		 *
		 * @param int $batch_size The batch size.
		 */
		$this->batch_size = (int) apply_filters( 'sensei_migration_parent_post_id_batch_size', $batch_size );
	}

	/**
	 * Run the migration.
	 *
	 * Processes lesson progress first, then quiz progress. Returns the number
	 * of rows processed, or 0 when all backfilling is complete.
	 *
	 * @since $$next-version$$
	 *
	 * @param bool $dry_run Whether to run the migration in dry-run mode.
	 * @return int The number of rows processed.
	 */
	public function run( bool $dry_run = true ) {
		$lessons_complete = get_option( self::LESSONS_COMPLETE_OPTION_NAME, false );

		if ( ! $lessons_complete ) {
			$processed = $this->backfill_lesson_progress( $dry_run );
			if ( $processed > 0 ) {
				return $processed;
			}

			// Lessons are done, mark complete and reset cursor for quiz phase.
			update_option( self::LESSONS_COMPLETE_OPTION_NAME, true );
			update_option( self::LAST_ID_OPTION_NAME, 0 );
		}

		$processed = $this->backfill_quiz_progress( $dry_run );
		if ( $processed > 0 ) {
			return $processed;
		}

		// All done — clean up cursor options.
		delete_option( self::LAST_ID_OPTION_NAME );
		delete_option( self::LESSONS_COMPLETE_OPTION_NAME );

		return 0;
	}

	/**
	 * Backfill lesson progress parent_post_id with the course ID.
	 *
	 * @param bool $dry_run Whether to run in dry-run mode.
	 * @return int The number of rows processed.
	 */
	private function backfill_lesson_progress( bool $dry_run ): int {
		global $wpdb;
		$table   = $wpdb->prefix . 'sensei_lms_progress';
		$last_id = (int) get_option( self::LAST_ID_OPTION_NAME, 0 );

		$select_query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id, post_id FROM {$table} WHERE type = 'lesson' AND parent_post_id IS NULL AND id > %d ORDER BY id ASC LIMIT %d",
			$last_id,
			$this->batch_size
		);

		if ( $dry_run ) {
			echo esc_html( $select_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $select_query );

		if ( empty( $rows ) ) {
			return 0;
		}

		$processed      = 0;
		$last_processed = $last_id;

		foreach ( $rows as $row ) {
			$course_id = (int) get_post_meta( (int) $row->post_id, '_lesson_course', true );

			if ( $course_id ) {
				if ( $dry_run ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					echo esc_html( $wpdb->prepare( "UPDATE {$table} SET parent_post_id = %d WHERE id = %d", $course_id, (int) $row->id ) . "\n" );
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table,
						array( 'parent_post_id' => $course_id ),
						array( 'id' => (int) $row->id ),
						array( '%d' ),
						array( '%d' )
					);

					if ( false === $result && '' !== $wpdb->last_error ) {
						$this->add_error( $wpdb->last_error );
					}
				}
			}

			$last_processed = (int) $row->id;
			++$processed;

			if ( $this->is_time_exceeded() ) {
				break;
			}
		}

		update_option( self::LAST_ID_OPTION_NAME, $last_processed );

		return $processed;
	}

	/**
	 * Backfill quiz progress parent_post_id with the lesson ID.
	 *
	 * @param bool $dry_run Whether to run in dry-run mode.
	 * @return int The number of rows processed.
	 */
	private function backfill_quiz_progress( bool $dry_run ): int {
		global $wpdb;
		$table   = $wpdb->prefix . 'sensei_lms_progress';
		$last_id = (int) get_option( self::LAST_ID_OPTION_NAME, 0 );

		$select_query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id, post_id FROM {$table} WHERE type = 'quiz' AND parent_post_id IS NULL AND id > %d ORDER BY id ASC LIMIT %d",
			$last_id,
			$this->batch_size
		);

		if ( $dry_run ) {
			echo esc_html( $select_query . "\n" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $select_query );

		if ( empty( $rows ) ) {
			return 0;
		}

		$processed      = 0;
		$last_processed = $last_id;

		foreach ( $rows as $row ) {
			$lesson_id = (int) get_post_meta( (int) $row->post_id, '_quiz_lesson', true );

			if ( $lesson_id ) {
				if ( $dry_run ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					echo esc_html( $wpdb->prepare( "UPDATE {$table} SET parent_post_id = %d WHERE id = %d", $lesson_id, (int) $row->id ) . "\n" );
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table,
						array( 'parent_post_id' => $lesson_id ),
						array( 'id' => (int) $row->id ),
						array( '%d' ),
						array( '%d' )
					);

					if ( false === $result && '' !== $wpdb->last_error ) {
						$this->add_error( $wpdb->last_error );
					}
				}
			}

			$last_processed = (int) $row->id;
			++$processed;

			if ( $this->is_time_exceeded() ) {
				break;
			}
		}

		update_option( self::LAST_ID_OPTION_NAME, $last_processed );

		return $processed;
	}
}
