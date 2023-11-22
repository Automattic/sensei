<?php
/**
 * File containing the class \Sensei\Internal\Installer\Schema.
 *
 * @package sensei
 * @since   4.16.1
 */

namespace Sensei\Internal\Installer;

use Sensei_Feature_Flags;

/**
 * Schema class.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Schema {
	/**
	 * Feature flags.
	 *
	 * @since 4.19.2
	 * @var Sensei_Feature_Flags
	 */
	private Sensei_Feature_Flags $feature_flags;

	/*
	 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WP 4.2, however, they moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * @since 4.16.1
	 * @var int
	 */
	const MAX_INDEX_LENGTH = 191;

	/**
	 * Constructor.
	 *
	 * @since 4.19.2
	 *
	 * @param Sensei_Feature_Flags $feature_flags Feature flags.
	 */
	public function __construct( Sensei_Feature_Flags $feature_flags ) {
		$this->feature_flags = $feature_flags;
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * WARNING: If you are modifying this method, make sure that its safe to call regardless of the state of database.
	 *
	 * This is called from `Installer::install()` method and is executed in-sync when the plugin is installed or updated.
	 *
	 * @internal
	 *
	 * @since 4.16.1
	 */
	public function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $this->get_query() );
	}

	/**
	 * Get the table schema query.
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * When adding or removing a table, make sure to update the list of tables in `Schema::get_tables()`.
	 *
	 * @internal
	 *
	 * @see https://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
	 * @since 4.16.1
	 *
	 * @return string The schema query.
	 */
	private function get_query(): string {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();

		$table_queries = [
			"{$wpdb->prefix}sensei_lms_progress"         => "
CREATE TABLE {$wpdb->prefix}sensei_lms_progress (
	id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
	post_id bigint UNSIGNED NOT NULL,
	user_id bigint UNSIGNED NOT NULL,
	parent_post_id bigint UNSIGNED,
	type varchar(20) NOT NULL,
	status varchar(20) NOT NULL,
	started_at datetime,
	completed_at datetime,
	created_at datetime NOT NULL,
	updated_at datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY user_progress (post_id, user_id, type),
	KEY status (status)
) $collate;
",
			"{$wpdb->prefix}sensei_lms_quiz_submissions" => "
CREATE TABLE {$wpdb->prefix}sensei_lms_quiz_submissions (
	id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
	quiz_id bigint UNSIGNED NOT NULL,
	user_id bigint UNSIGNED NOT NULL,
	final_grade decimal(5,2),
	created_at datetime NOT NULL,
	updated_at datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY user_quiz (quiz_id, user_id)
) $collate;
",
			"{$wpdb->prefix}sensei_lms_quiz_answers"     => "
CREATE TABLE {$wpdb->prefix}sensei_lms_quiz_answers (
	id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
	submission_id bigint UNSIGNED NOT NULL,
	question_id bigint UNSIGNED NOT NULL,
	value longtext NOT NULL,
	created_at datetime NOT NULL,
	updated_at datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY question_submission (submission_id, question_id)
) $collate;
",
			"{$wpdb->prefix}sensei_lms_quiz_grades"      => "
CREATE TABLE {$wpdb->prefix}sensei_lms_quiz_grades (
	id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
	answer_id bigint UNSIGNED NOT NULL,
	question_id bigint UNSIGNED NOT NULL,
	points int NOT NULL,
	feedback longtext,
	created_at datetime NOT NULL,
	updated_at datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY question_answer (answer_id, question_id)
) $collate;
",
		];

		$query = '';
		foreach ( $this->get_tables() as $table ) {
			if ( isset( $table_queries[ $table ] ) ) {
				$query .= $table_queries[ $table ];
			}
		}

		return $query;
	}

	/**
	 * Return a list of tables. Used to make sure all tables are dropped when uninstalling the plugin
	 * in a single site or multi-site environment.
	 *
	 * @internal
	 *
	 * @since 4.16.1
	 *
	 * @return array Database tables.
	 */
	public function get_tables(): array {
		global $wpdb;

		$tables = [];
		if ( $this->feature_flags->is_enabled( 'tables_based_progress' ) ) {
			$tables[] = "{$wpdb->prefix}sensei_lms_progress";
			$tables[] = "{$wpdb->prefix}sensei_lms_quiz_submissions";
			$tables[] = "{$wpdb->prefix}sensei_lms_quiz_answers";
			$tables[] = "{$wpdb->prefix}sensei_lms_quiz_grades";
		}

		/**
		 * Filter the list of known tables.
		 *
		 * If plugins need to add new tables, they can inject them here.
		 *
		 * @since 4.16.1
		 *
		 * @hook sensei_lms_schema_get_tables
		 *
		 * @param {array} $tables An array of Sensei specific database table names.
		 * @return {array} Filtered array of Sensei specific database table names.
		 */
		$tables = apply_filters( 'sensei_lms_schema_get_tables', $tables );

		return $tables;
	}
}

