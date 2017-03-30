<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_Domain_Models_Course_Data_Store_Cpt implements Sensei_Domain_Models_Data_Store {
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
            do_action( 'sensei_trash_course', $id );
        }
    }

    /**
     * @param $entity Sensei_Domain_Models_Model_Abstract
     * @return mixed
     */
    public function upsert( $entity, $fields, $meta_fields = array() ) {
        //        $fields['meta_input'] = $meta_fields;
        $success = wp_insert_post( $fields, true );
        if ( is_wp_error( $success ) ) {
            //todo: something wrong
            return $success;
        }
        return absint( $success );
    }

    public function get_entities() {
        $query = new WP_Query( array(
            'post_type' => 'course',
            'post_status' => 'any'
        ) );
        return $query->get_posts();
    }

    public function get_entity( $course_id ) {
        $course = get_post( absint( $course_id ) );
        return !empty( $course ) && $course->post_type === 'course' ? $course->to_array() : null;
    }
    
    public function get_meta_field_value( $course, $field_declaration ) {
        $map_from = $field_declaration->get_name_to_map_from();
        return get_post_meta( $course->get_id(), $map_from, true );
    }
}