<?php

class Sensei_Db_Query_Learners {

    public function __construct( $args ) {
        $this->per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 25;
        $this->offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
        $this->course_id = isset( $args['course_id'] ) ? intval( $args['course_id'] ) : 0;
        $this->lesson_id = isset( $args['lesson_id'] ) ? intval( $args['lesson_id'] ) : 0;
        $this->order_by = isset( $args['orderby'] ) ? $args['orderby'] : '';
        $this->order_type = isset( $args['order'] ) ? $args['order'] : 'ASC';
        $this->search = isset( $args['search'] ) ? $args['search'] : '';
        $this->filter_by_course_id = isset( $args['filter_by_course_id'] ) ? absint( $args['filter_by_course_id'] ) : 0;
        $this->filter_type = isset( $args['filter_type'] ) ? $args['filter_type'] : 'inc';

        $this->total_items = 0;
    }

    private function build_query( $type = 'paginate' ) {
        global $wpdb;

        $sql =
            "SELECT u.ID AS 'user_id', u.user_nicename, u.user_login, u.user_email, " .
            "GROUP_CONCAT(c.comment_post_ID, '|', IF(c.comment_approved = 'complete', 'c', 'p' )) AS 'course_statuses'," .
            " COUNT(c.comment_approved) AS 'course_count'" .
            " FROM `{$wpdb->users}` AS u" .
            " LEFT JOIN (SELECT * from `{$wpdb->comments}` as sc WHERE sc.comment_type = 'sensei_course_status') as c ON u.ID = c.user_id";
        if ( !empty( $this->search ) || !empty( $this->filter_by_course_id ) ) {
            $sql .= ' WHERE';
            if ( !empty( $this->search ) ) {
                $search_term = $wpdb->prepare('%s', $this->search . '%');
                $sql .= " u.user_login LIKE {$search_term} OR u.user_nicename LIKE {$search_term} OR u.user_email LIKE {$search_term}";
                if (!empty( $this->filter_by_course_id )) {
                    $sql .= ' AND';
                }
            }
            if ( !empty( $this->filter_by_course_id ) ) {
                $eq = ('inc' == $this->filter_type ) ? '=' : '!=';
                $sql .= " c.comment_post_ID {$eq} {$this->filter_by_course_id} AND c.comment_approved IS NOT NULL";
            }
        }

        $sql .= " GROUP BY u.ID";
        if ( ! empty( $this->order_by ) && 'learner' === $this->order_by && in_array($this->order_type, array('ASC', 'DESC')) ) {
            $sql .= $wpdb->prepare( " ORDER BY u.user_login %s", array( $this->order_type ) );
        }
        if ($type === 'paginate') {
            $sql .= $wpdb->prepare( " LIMIT %d OFFSET %d ", array( $this->per_page, $this->offset ) );
            return $sql;
        }
        return $sql;
    }

    public function get_all() {
        global $wpdb;
        $sql = $this->build_query();

        $results = $wpdb->get_results( $sql );
        $this->total_items = intval( $wpdb->query( $this->build_query('count') ) );
        return $results;
    }
}