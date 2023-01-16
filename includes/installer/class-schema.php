<?php
/**
 * File containing the class \Sensei\Installer\Schema.
 *
 * @package sensei
 * @since   $$next-version$$
 */

namespace Sensei\Installer;

/**
 * Schema class.
 *
 * @since $$next-version$$
 */
class Schema {
	/*
	 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WP 4.2, however, they moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * @since $$next-version$$
	 * @var int
	 */
	const MAX_INDEX_LENGTH = 191;

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * WARNING: If you are modifying this method, make sure that its safe to call regardless of the state of database.
	 *
	 * This is called from `Installer::install()` method and is executed in-sync when the plugin is installed or updated.
	 *
	 * @since $$next-version$$
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
	 * @see https://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table
	 * @since $$next-version$$
	 *
	 * @return string The schema query.
	 */
	private function get_query(): string {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();

		return "
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
	update_at datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY user_progress (post_id, user_id, type),
	KEY status (status)
) $collate;";
	}

	/**
	 * Return a list of tables. Used to make sure all tables are dropped when uninstalling the plugin
	 * in a single site or multi-site environment.
	 *
	 * @since $$next-version$$
	 *
	 * @return array Database tables.
	 */
	public function get_tables(): array {
		global $wpdb;

		$tables = [
			"{$wpdb->prefix}sensei_lms_progress",
		];

		/**
		 * Filter the list of known tables.
		 *
		 * If plugins need to add new tables, they can inject them here.
		 *
		 * @since $$next-version$$
		 *
		 * @param array $tables An array of Sensei Pro specific database table names.
		 */
		$tables = apply_filters( 'sensei_lms_schema_get_tables', $tables );

		return $tables;
	}
}

