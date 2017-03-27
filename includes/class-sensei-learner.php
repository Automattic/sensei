<?php

/**
 * Responsible for all student specific functionality and helper functions
 *
 * @package Users
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Learner{

    /**
     * Get the students full name
     *
     * This function replaces Sensei_Learner_Managment->get_learner_full_name
     * @since 1.9.0
     *
     * @param $user_id
     * @return bool|mixed|void
     */
    public static function get_full_name( $user_id ){

        $full_name = '';

        if( empty( $user_id ) || ! ( 0 < intval( $user_id ) )
            || !( get_userdata( $user_id ) ) ){
            return false;
        }

        // get the user details
        $user = get_user_by( 'id', $user_id );

        if( ! empty( $user->first_name  ) && ! empty( $user->last_name  )  ){

            $full_name = trim( $user->first_name   ) . ' ' . trim( $user->last_name  );

        }else{

            $full_name =  $user->display_name;

        }

        /**
         * Filter the user full name from the get_learner_full_name function.
         *
         * @since 1.8.0
         * @param $full_name
         * @param $user_id
         */
        return apply_filters( 'sensei_learner_full_name' , $full_name , $user_id );

    }// end get_full_name

    public static function get_all_active_learner_ids_for_course( $course_id ) {
        $post_id = absint( $course_id );

        if( !$post_id ) {
            return array();
        }

        $activity_args = array(
            'post_id' => $post_id,
            'type' => 'sensei_course_status',
            'status' => 'any'
        );


        $learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

        if ( !is_array($learners) ) {
            $learners = array( $learners );
        }

        $learner_ids = wp_list_pluck( $learners, 'user_id' );

        return $learner_ids;
    }

    public static function get_all( $args ) {

        $user_ids = false;
        $post_id = 0;
        $activity = '';

        if( isset( $args['lesson_id'] ) ) {
            $post_id = intval( $args['lesson_id'] );
            $activity = 'sensei_lesson_status';
        }
        elseif( isset( $args['course_id'] ) ) {
            $post_id = intval( $args['course_id'] );
            $activity = 'sensei_course_status';
        }

        if( ! $post_id || ! $activity ) {
            return array();
        }

        $activity_args = array(
            'post_id' => $post_id,
            'type' => $activity,
            'status' => 'any',
            'number' => $args['per_page'],
            'offset' => $args['offset'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
        );

        // Searching users on statuses requires sub-selecting the statuses by user_ids
        if ( $args['search'] ) {
            $user_args = array(
                'search' => '*' . $args['search'] . '*',
                'fields' => 'ID'
            );
            // Filter for extending
            $user_args = apply_filters( 'sensei_learners_search_users', $user_args );
            if ( !empty( $user_args ) ) {
                $learners_search = new WP_User_Query( $user_args );
                $activity_args['user_id'] = $learners_search->get_results();
            }
        }

        $activity_args = apply_filters( 'sensei_learners_filter_users', $activity_args );

        // WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so instead do this twice
        $total_learners = Sensei_Utils::sensei_check_for_activity( array_merge( $activity_args, array('count' => true, 'offset' => 0, 'number' => 0) ) );
        // Ensure we change our range to fit (in case a search threw off the pagination) - Should this be added to all views?
        if ( $total_learners < $activity_args['offset'] ) {
            $new_paged = floor( $total_learners / $activity_args['number'] );
            $activity_args['offset'] = $new_paged * $activity_args['number'];
        }
        $learners = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
        // Need to always return an array, even with only 1 item
        if ( !is_array($learners) ) {
            $learners = array( $learners );
        }

        return $learners;
    } // End get_learners()
}