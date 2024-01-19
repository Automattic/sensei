<?php
/**
 * File containing the Earaser class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Installer;

/**
 * Earaer class. Used to delete all progress tables.
 *
 * @internal
 *
 * @since 4.20.0
 */
class Eraser {

	/**
	 * Drop all progress tables.
	 *
	 * @return array
	 */
	public function drop_tables(): array {
		global $wpdb;

		$results = array();
		foreach ( $this->get_tables() as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( "DROP TABLE IF EXISTS $table" );
				$results[] = $table;
			}
		}

		/**
		 * Fires after progress tables are deleted.
		 *
		 * @since 4.19.0
		 *
		 * @param array $tables List of deleted tables.
		 */
		do_action( 'sensei_tools_progress_tables_deleted', $results );

		return $results;
	}

	/**
	 * Get the tables to delete.
	 *
	 * @return array
	 */
	public function get_tables(): array {
		global $wpdb;

		return array(
			"{$wpdb->prefix}sensei_lms_progress",
			"{$wpdb->prefix}sensei_lms_quiz_submissions",
			"{$wpdb->prefix}sensei_lms_quiz_answers",
			"{$wpdb->prefix}sensei_lms_quiz_grades",
		);
	}
}
