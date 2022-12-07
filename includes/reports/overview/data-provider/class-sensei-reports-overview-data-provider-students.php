<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Students class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Reports_Overview_Data_Provider_Students
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_Data_Provider_Students implements Sensei_Reports_Overview_Data_Provider_Interface {
	/**
	 * Total number of students found with given criteria.
	 *
	 * @var int Total number of items
	 */
	private $last_total_items = 0;

	/**
	 * Contains start date and time for filtering.
	 *
	 * @var string|null
	 */
	private $date_from;

	/**
	 * Contains end date and time for filtering.
	 *
	 * @var string|null
	 */
	private $date_to;

	/**
	 * Get the data for the overview report.
	 *
	 * @param array $filters Filters to apply to the data.
	 *
	 * @return array
	 */
	public function get_items( array $filters ): array {
		global $wpdb;
		$enrolment_prefix = $wpdb->get_blog_prefix() . \Sensei_Course_Enrolment::META_PREFIX_ENROLMENT_RESULTS;

		$this->date_from = $filters['last_activity_date_from'] ?? null;
		$this->date_to   = $filters['last_activity_date_to'] ?? null;

		$query_args = array(
			'fields'           => [ 'ID', 'user_login', 'user_email', 'user_registered', 'display_name' ],
			'orderby'          => $filters['orderby'] ?? '',
			'order'            => $filters['order'] ?? 'ASC',
			'meta_compare_key' => 'LIKE',
			'meta_key'         => $enrolment_prefix, // phpcs:ignore WordPress.DB.SlowDBQuery -- Filter enrolled users only.
		);

		$query_args = array_merge( $query_args, $filters );
		if ( ! empty( $filters['search'] ) ) {
			$query_args['search'] = '*' . trim( $filters['search'], '*' ) . '*';
		}

		/**
		 * Filter the WP_User_Query arguments
		 *
		 * @since 1.6.0
		 * @param $query_args
		 */
		$query_args = apply_filters( 'sensei_analysis_overview_filter_users', $query_args );

		add_action( 'pre_user_query', [ $this, 'group_by_users' ] );

		if ( $this->get_is_last_activity_filter_enabled() ) {
			add_action( 'pre_user_query', [ $this, 'add_last_activity_to_user_query' ] );
			add_action( 'pre_user_query', [ $this, 'filter_users_by_last_activity' ] );

			if ( ! empty( $query_args['orderby'] ) && 'last_activity_date' === $query_args['orderby'] ) {
				add_action( 'pre_user_query', [ $this, 'add_orderby_custom_field_to_user_query' ] );
			}
		}

		add_action( 'pre_user_query', [ $this, 'add_pre_user_query_hook' ] );

		$wp_user_search = new WP_User_Query( $query_args );

		remove_action( 'pre_user_query', [ $this, 'add_pre_user_query_hook' ] );
		remove_action( 'pre_user_query', [ $this, 'add_orderby_custom_field_to_user_query' ] );
		remove_action( 'pre_user_query', [ $this, 'add_last_activity_to_user_query' ] );
		remove_action( 'pre_user_query', [ $this, 'filter_users_by_last_activity' ] );

		$learners               = $wp_user_search->get_results();
		$this->last_total_items = $wp_user_search->get_total();

		return $learners;
	}

	/**
	 * Check if last activity filter is enabled.
	 *
	 * @return {boolean} Whether last activity filter is enabled.
	 */
	public function get_is_last_activity_filter_enabled() {
		/**
		 * Filters if the last activity filter by date and the last activity sorting are enabled in
		 * the students report.
		 *
		 * @hook  sensei_students_report_last_activity_filter_enabled
		 * @since 4.6.4
		 *
		 * @param {boolean} $enabled Whether the students last activity filter is enabled.
		 *
		 * @return {boolean} Whether the students last activity filter is enabled.
		 */
		return apply_filters( 'sensei_students_report_last_activity_filter_enabled', true );
	}

	/**
	 * Add a user query hook before querying the users.
	 * This allows for third parties to alter the query.
	 *
	 * @since  4.6.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_pre_user_query_hook( WP_User_Query $query ) {
		/**
		 * Fires before the user query is executed.
		 *
		 * @hook sensei_reports_overview_students_data_provider_pre_user_query
		 * @since 4.6.0
		 *
		 * @param {WP_User_Query} $query The user query.
		 */
		do_action( 'sensei_reports_overview_students_data_provider_pre_user_query', $query );
	}

	/**
	 * Filter the users to ones enrolled in a course.
	 *
	 * @deprecated 4.6.4
	 *
	 * @since  4.4.1
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function only_course_enrolled_users( WP_User_Query $query ) {
		_deprecated_function( __METHOD__, '4.6.4' );

		global $wpdb;

		$query->query_from .= "
			INNER JOIN {$wpdb->comments}
				ON {$wpdb->comments}.user_id = {$wpdb->users}.ID
				AND {$wpdb->comments}.comment_type = 'sensei_course_status'
		";

		$query->query_where .= " GROUP BY {$wpdb->users}.ID ";
	}

	/**
	 * Group query by users.
	 *
	 * @since  4.6.4
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function group_by_users( WP_User_Query $query ) {
		global $wpdb;

		$query->query_where .= " GROUP BY {$wpdb->users}.ID ";
	}

	/**
	 * Order query based on the custom field.
	 *
	 * @since  4.3.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_orderby_custom_field_to_user_query( WP_User_Query $query ) {
		$query->query_orderby = 'ORDER BY ' . $query->query_vars['orderby'] . ' ' . $query->query_vars['order'];
	}

	/**
	 * Add the `last_activity` field to the user query.
	 *
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function add_last_activity_to_user_query( WP_User_Query $query ) {
		global $wpdb;

		$query->query_fields .= ", (
			SELECT MAX({$wpdb->comments}.comment_date_gmt)
			FROM {$wpdb->comments}
			USE INDEX (sensei_comment_type_user_id)
			WHERE {$wpdb->comments}.user_id = {$wpdb->users}.ID
			AND {$wpdb->comments}.comment_approved IN ('complete', 'passed', 'graded')
			AND {$wpdb->comments}.comment_type = 'sensei_lesson_status'
			ORDER BY {$wpdb->comments}.comment_date_gmt DESC
		) AS last_activity_date";
	}

	/**
	 * Filter the users by last activity start/end date.
	 *
	 * This action should be called after `Sensei_Analysis_Overview_List_Table::add_last_activity_to_user_query`.
	 *
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function filter_users_by_last_activity( WP_User_Query $query ) {
		global $wpdb;

		if ( ! $this->date_from && ! $this->date_to ) {
			return;
		}

		$query->query_where .= ' HAVING 1 = 1';

		// Filter by start date.
		if ( $this->date_from ) {
			$query->query_where .= $wpdb->prepare(
				' AND last_activity_date >= %s',
				$this->date_from
			);
		}

		// Filter by end date.
		if ( $this->date_to ) {
			$query->query_where .= $wpdb->prepare(
				' AND last_activity_date <= %s',
				$this->date_to
			);
		}
	}

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @return int
	 */
	public function get_last_total_items(): int {
		return $this->last_total_items;
	}
}
