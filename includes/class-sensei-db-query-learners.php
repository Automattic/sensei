<?php

class Sensei_Db_Query_Learners {

    public function __construct( $args ) {
        $this->per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 25;
        $this->offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
        $this->course_id = isset( $args['course_id'] ) ? intval( $args['course_id'] ) : 0;
        $this->lesson_id = isset( $args['lesson_id'] ) ? intval( $args['lesson_id'] ) : 0;
        $this->order_by = isset( $args['orderby'] ) ? $args['orderby'] : 'learner';
        $this->order_type = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'ASC';
        $this->search = isset( $args['search'] ) ? $args['search'] : '';
        $this->filter_by_course_id = isset( $args['filter_by_course_id'] ) ? absint( $args['filter_by_course_id'] ) : 0;
        $this->filter_type = isset( $args['filter_type'] ) ? $args['filter_type'] : 'inc';

        $this->total_items = 0;
    }

    private function build_query( $type = 'paginate' ) {
        global $wpdb;

        $user_query_args = array();
		if ( ! empty( $this->search ) ) {
			// $user_query_args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email' );
			$user_query_args['search'] = '*' . sanitize_text_field( $this->search ) . '*';
		}

		$user_query_args['fields'] = 'ids';
		$user_query_args['number'] = -1;
		$user_query = new WP_User_Query( $user_query_args );
		$matching_user_ids = array_map( 'absint', $user_query->get_results() );

        $sql = "SELECT SQL_CALC_FOUND_ROWS `u`.`ID` AS 'user_id',
              `u`.`user_nicename`,
              `u`.`user_login`,
              `u`.`user_email`,
              GROUP_CONCAT(`c`.`comment_post_ID`, '|', IF(`c`.`comment_approved` = 'complete', 'c', 'p' )) AS 'course_statuses',
              COUNT(`c`.`comment_approved`) AS 'course_count'
              FROM `{$wpdb->users}` AS `u`
            LEFT JOIN (
              SELECT * FROM `{$wpdb->comments}` AS `sc`
              WHERE `sc`.`comment_type` = 'sensei_course_status'
            ) AS `c` ON `u`.`ID` = `c`.`user_id`";
		$sql .= ' WHERE';
		$user_id_in = empty( $matching_user_ids ) ? 'false' : implode( ',', $matching_user_ids );
		$sql .= " u.ID IN ({$user_id_in})";
		if ( ! empty ( $this->filter_by_course_id ) ) {
			$sql .= ' AND';
		}
		if ( ! empty( $this->filter_by_course_id ) ) {
			$eq = ('inc' == $this->filter_type ) ? '=' : '!=';
			$sql .= " c.comment_post_ID {$eq} {$this->filter_by_course_id} AND c.comment_approved IS NOT NULL";
		}

        $sql .= " GROUP BY `u`.`ID`";
        if ( ! empty( $this->order_by ) && 'learner' === $this->order_by && in_array( $this->order_type, array('ASC', 'DESC') ) ) {
        	$order_type = $this->order_type;
            $sql .= " ORDER BY `u`.`user_login` {$order_type}";
        }

        if ($type === 'paginate') {
            $sql .= $wpdb->prepare( " LIMIT %d OFFSET %d ", array( $this->per_page, $this->offset ) );
        }

        return $sql;
    }

    public function get_all() {
        global $wpdb;
        $sql = $this->build_query();

        $results = $wpdb->get_results( $sql );
        $this->total_items = intval( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) );
        return $results;
    }
}
