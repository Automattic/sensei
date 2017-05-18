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

}