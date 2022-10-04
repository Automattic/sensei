<?php
/**
 * File containing the Sensei_No_Users_Table_Relationship class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that handles a special case of environments that have the users table in a separate
 * database from the other tables.
 *
 * @since 4.6.4
 */
class Sensei_No_Users_Table_Relationship {
	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_No_Users_Table_Relationship constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() : self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() : void {
		// Students report.
		add_filter( 'sensei_students_report_last_activity_filter_enabled', [ $this, 'can_use_users_relationship' ] );
		add_filter( 'sensei_analysis_overview_users_columns_sortable', [ $this, 'filter_analysis_overview_users_columns_sortable' ] );
		add_filter( 'sensei_analysis_overview_column_data', [ $this, 'filter_analysis_overview_column_data' ], 10, 3 );
	}

	/**
	 * Check if it's possible to use a relationship between users and posts table. It's used to
	 * check environments where the users table lives in a different places and the relationship
	 * doesn't work.
	 *
	 * @access private
	 *
	 * @return boolean Whether the users relationship is possible.
	 */
	public function can_use_users_relationship() : bool {
		/**
		 * Filters if site environment is able to make queries including a relationship between users
		 * table and others.
		 *
		 * @hook  sensei_can_use_users_relationship
		 * @since 4.6.4
		 *
		 * @param {null} $can_use_users_relationship Default value is `null`. With this value the
		 *                                           filter is ignored.
		 *
		 * @return {boolean|null} Whether the site environment is able to make queries including a
		 *                        relationship between users table and others..
		 */
		$filtered_can_use_users_relationship = apply_filters( 'sensei_can_use_users_relationship', null );

		if ( null !== $filtered_can_use_users_relationship ) {
			return $filtered_can_use_users_relationship;
		}

		$can_use_users_relationship = wp_cache_get( 'sensei_can_use_users_relationship' );

		if ( false === $can_use_users_relationship ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Database relationship check.
			$result     = $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p LIMIT 1;" );
			$have_posts = null !== $result;

			/**
			 * If site doesn't have posts yet, it considers that we can't use the user relationship,
			 * but it might not be true because we can't check it. This case is not cached, in case
			 * it changes.
			 */
			if ( ! $have_posts ) {
				return false;
			}

			// Temporarily suppress errors for this DB check.
			$previous_suppress_errors = $wpdb->suppress_errors;
			$wpdb->suppress_errors( true );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Database relationship check.
			$result                     = $wpdb->get_var( "SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author LIMIT 1;" );
			$can_use_users_relationship = null !== $result ? 1 : 0;

			$wpdb->suppress_errors( $previous_suppress_errors );
			wp_cache_set( 'sensei_can_use_users_relationship', $can_use_users_relationship );
		}

		return 1 === $can_use_users_relationship;
	}

	/**
	 * Filters the sortable columns from students report.
	 *
	 * @access private
	 *
	 * @param array $columns Sortable columns object.
	 *
	 * @return array Filtered columns.
	 */
	public function filter_analysis_overview_users_columns_sortable( $columns ) {
		if ( ! $this->can_use_users_relationship() ) {
			unset( $columns['last_activity'] );
		}

		return $columns;
	}

	/**
	 * Undocumented function
	 *
	 * @access private
	 *
	 * @param array                               $columns Data columns.
	 * @param object                              $item    Item data object.
	 * @param Sensei_Analysis_Overview_List_Table $object  Class instance.
	 *
	 * @return array Filtered columns.
	 */
	public function filter_analysis_overview_column_data( $columns, $item, $object ) {
		if ( ! $this->can_use_users_relationship() ) {
			$last_activity_date = $this->get_user_last_activity_date( $item->ID );

			if ( $last_activity_date ) {
				$columns['last_activity'] = $object->csv_output ? $last_activity_date : Sensei_Utils::format_last_activity_date( $last_activity_date );
			}
		}

		return $columns;
	}

	/**
	 * Get the last activity date from a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string Last activity date string from the database.
	 */
	private function get_user_last_activity_date( $user_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query to get last activity date.
		return $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT MAX({$wpdb->comments}.comment_date_gmt)
				FROM {$wpdb->comments}
				WHERE {$wpdb->comments}.user_id = %d
				AND {$wpdb->comments}.comment_approved IN ('complete', 'passed', 'graded')
				AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
				ORDER BY {$wpdb->comments}.comment_date_gmt DESC",
				$user_id
			)
		);
	}
}
