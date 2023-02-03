<?php

/**
 * Class Sensei_Db_Query_Learners
 *
 * Helper to fetch learners.
 */
class Sensei_Db_Query_Learners {

	/**
	 * Sensei_Db_Query_Learners constructor.
	 *
	 * @param array $args Arguments to build query.
	 */
	public function __construct( $args ) {
		$this->per_page            = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 25;
		$this->offset              = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
		$this->course_id           = isset( $args['course_id'] ) ? intval( $args['course_id'] ) : 0;
		$this->lesson_id           = isset( $args['lesson_id'] ) ? intval( $args['lesson_id'] ) : 0;
		$this->order_by            = isset( $args['orderby'] ) ? $args['orderby'] : 'learner';
		$this->order_type          = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'ASC';
		$this->search              = isset( $args['search'] ) ? $args['search'] : '';
		$this->filter_by_course_id = isset( $args['filter_by_course_id'] ) ? absint( $args['filter_by_course_id'] ) : 0;
		$this->filter_type         = isset( $args['filter_type'] ) ? $args['filter_type'] : 'inc';

		$this->total_items = 0;
	}

	/**
	 * Build the SQL query for getting users.
	 *
	 * @return string
	 */
	private function build_query() {
		global $wpdb;

		$matching_user_ids = null;
		if ( is_multisite() || ! empty( $this->search ) ) {
			$user_query_args = array();
			if ( ! empty( $this->search ) ) {
				$user_query_args['search'] = '*' . sanitize_text_field( $this->search ) . '*';
			}

			$user_query_args['fields'] = 'ids';
			$user_query_args['number'] = -1;

			$user_query        = new WP_User_Query( $user_query_args );
			$matching_user_ids = $user_query->get_results();
		}

		if ( ! empty( $this->filter_by_course_id ) ) {
			$eq = ( 'inc' === $this->filter_type ) ? '=' : '!=';

			$sql = "
				SELECT
					`cf`.`user_id`
				FROM `{$wpdb->comments}` AS `cf`
					WHERE `cf`.`comment_type` = 'sensei_course_status'
					AND `cf`.comment_post_ID {$eq} {$this->filter_by_course_id}
					AND `cf`.comment_approved IS NOT NULL";

			$results  = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$user_ids = wp_list_pluck( $results, 'user_id' );

			if ( ! empty( $matching_user_ids ) ) {
				$matching_user_ids = array_intersect( $user_ids, $matching_user_ids );
			} else {
				$matching_user_ids = $user_ids;
			}
		}

		/*
		 * Return empty string for `course_statuses` and zero for `course_count` for backward compatibility.
		 */
		$sql = "
			SELECT SQL_CALC_FOUND_ROWS
				`u`.`ID` AS 'user_id',
				`u`.`user_nicename`,
				`u`.`user_login`,
				`u`.`user_email`,
				'' AS 'course_statuses',
				0 AS 'course_count'
			FROM `{$wpdb->users}` AS `u`";

		$sql .= ' WHERE 1=1';

		if ( null !== $matching_user_ids ) {
			$matching_user_ids = array_map( 'absint', $matching_user_ids );
			$user_id_in        = empty( $matching_user_ids ) ? 'false' : implode( ',', $matching_user_ids );
			$sql              .= " AND u.ID IN ({$user_id_in})";
		}

		$sql .= ' GROUP BY `u`.`ID`';
		if ( ! empty( $this->order_by ) && in_array( $this->order_type, array( 'ASC', 'DESC' ), true ) ) {
			$order_type = $this->order_type;
			$order_by   = $this->order_by;
			// Switch case to be used when the value in the 'order_by' param needs modifying to work in the db.
			switch ( $this->order_by ) {
				case 'learner':
					$order_by = 'u.user_login';
					break;
				default:
					break;
			}
			$sql .= " ORDER BY {$order_by} {$order_type}";
		}

		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d ', array( $this->per_page, $this->offset ) );

		return $sql;
	}

	/**
	 * Get last activity date by users.
	 *
	 * @param int[] $user_ids User IDs to get the last activity date.
	 *
	 * @return array Last activity date array.
	 */
	private function get_last_activity_date_by_users( $user_ids ) {
		global $wpdb;

		if ( empty( $user_ids ) ) {
			return [];
		}

		$in_placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders created dinamically.
			$wpdb->prepare(
				"
				SELECT cm.user_id, MAX(cm.comment_date_gmt) AS last_activity_date
				FROM {$wpdb->comments} cm
				WHERE cm.user_id IN ( {$in_placeholders} )
				AND cm.comment_approved IN ('complete', 'passed', 'graded')
				AND cm.comment_type = 'sensei_lesson_status'
				GROUP BY user_id", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dinamically.
				$user_ids
			),
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			OBJECT_K
		);

		if ( ! $results ) {
			return [];
		}

		return $results;
	}

	/**
	 * Get the results of the query.
	 *
	 * @return array
	 */
	public function get_all() {
		global $wpdb;
		$sql = $this->build_query();

		/**
		 * Filter the query to get learners based on the current search arguments.
		 *
		 * @hook sensei_learners_query
		 * @since 4.11.0
		 *
		 * @param {string} $sql SQL query
		 *
		 * @return {Sensei_Db_Query_Learners} Query builder instance.
		 */
		$sql = apply_filters( 'sensei_learners_query', $sql, $this );

		$results                     = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Created inside the build_query method.
		$this->total_items           = intval( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$user_ids                    = wp_list_pluck( $results, 'user_id' );
		$last_activity_date_by_users = $this->get_last_activity_date_by_users( $user_ids );

		$results = array_map(
			function( $row ) use ( $last_activity_date_by_users ) {
				$user_id = $row->user_id;

				$row->last_activity_date = ! empty( $last_activity_date_by_users[ $user_id ] )
					? $last_activity_date_by_users[ $user_id ]->last_activity_date
					: null;

				return $row;
			},
			$results
		);

		return $results;
	}
}
