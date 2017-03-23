<?php


class Sensei_Domain_Models_Course_Data_Store_Cpt {
    /**
     * @param $course Sensei_Domain_Models_Course
     * @param array $args
     */
    public function delete( $course, $args = array() ) {
        $id        = $course->get_id();

        $args = wp_parse_args( $args, array(
            'force_delete' => false,
        ) );

        if ( $args['force_delete'] ) {
            wp_delete_post( $course->get_id() );
            $course->set_value( 'id', 0 );
            do_action( 'sensei_delete_course', $id );
        } else {
            wp_trash_post( $course->get_id() );
            $course->set_value( 'status', 'trash' );
            do_action( 'sensei_delete_course', $id );
        }
    }
}