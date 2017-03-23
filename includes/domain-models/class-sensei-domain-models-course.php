<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Course
 * @package Domain_Models
 */
class Sensei_Domain_Models_Course extends Sensei_Domain_Models_Model_Abstract {

    /**
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @return mixed
     */
    protected function get_meta_field_value($field_declaration ) {
        $map_from = $field_declaration->get_name_to_map_from();
        return get_post_meta( $this->get_id(), $map_from, true );
    }

    public function get_id() {
        return $this->id;
    }

    public static function declare_fields() {
        return array(
            self::field()
                ->with_name( 'id' )
                ->map_from( 'ID' )
                ->with_value_type('int')
                ->with_before_return( 'as_uint' ),
            self::field()
                ->with_name( 'title' )
                ->map_from( 'post_title' )
                ->with_value_type('string')
                ->required( true ),
            self::field()
                ->with_name( 'author' )
                ->map_from( 'post_author' )
                ->with_value_type('int')
                ->with_before_return( 'as_uint' ),
            self::field()
                ->with_name( 'content' )
                ->with_value_type('string')
                ->map_from( 'post_content' ),
            self::field()
                ->with_name( 'excerpt' )
                ->with_value_type('string')
                ->map_from( 'post_excerpt' ),
            self::field()
                ->with_name( 'type' )
                ->with_value_type('string')
                ->with_default_value( 'course' )
                ->map_from( 'post_type' ),
            self::field()
                ->with_name( 'status' )
                ->with_value_type('string')
                ->map_from( 'post_status' ),

            self::derived_field()
                ->with_name( 'modules' )
                ->map_from( 'course_module_ids' )
                ->with_json_name( 'module_ids' ),
            self::derived_field()
                ->with_name( 'module_order' )
                ->map_from( 'module_order' ),
            self::derived_field()
                ->with_name( 'lessons' )
                ->map_from( 'course_lessons' )
                ->not_visible(),

            self::meta_field()
                ->with_name( 'prerequisite' )
                ->map_from( '_course_prerequisite' )
                ->with_value_type('int')
                ->with_before_return( 'as_uint' ),
            self::meta_field()
                ->with_name( 'featured' )
                ->map_from( '_course_featured' )
                ->with_value_type('boolean')
                ->with_before_return( 'as_bool' )
                ->with_json_name( 'is_featured' ),
            self::meta_field()
                ->with_name( 'video_embed' )
                ->map_from( '_course_video_embed' ),
            self::meta_field()
                ->with_name( 'woocommerce_product' )
                ->map_from( '_course_woocommerce_product' )
                ->with_value_type('int')
                ->with_before_return( 'as_uint' ),
            self::meta_field()
                ->with_name( 'lesson_order' )
                ->map_from( '_lesson_order' )
        );
    }

    protected function course_module_ids() {
        $modules = Sensei()->modules->get_course_modules( absint( $this->id ) );
        return array_map( 'absint', wp_list_pluck( $modules, 'term_id' ) );
    }

    /**
     * Get module order callable
     * @return array
     */
    protected function module_order() {
        $modules = Sensei()->modules->get_course_module_order( absint( $this->id ) );
        return ( empty( $modules ) ) ? array() : array_map( 'absint', $modules );
    }

    public static function get_entity( $course_id ) {
        $course = get_post( absint( $course_id ) );
        return !empty( $course ) && $course->post_type === 'course' ? $course : null;
    }

    public static function get_entities() {
        $query = new WP_Query( array(
            'post_type' => 'course',
            'post_status' => 'any'
        ) );
        return $query->get_posts();
    }
    
    public function save_entity( $fields, $meta_fields = array() ) {
//        $fields['meta_input'] = $meta_fields;
        $success = wp_insert_post( $fields, true );
        if ( is_wp_error( $success ) ) {
            //todo: something wrong
            return $success;
        }
        return absint( $success );
    }

    public function delete_entity() {
        $store = new Sensei_Domain_Models_Course_Data_Store_Cpt();
        $store->delete( $this );
        return $this;
    }

    /**
     * validate object
     * @return bool|WP_Error
     */
    public function validate() {
        $validation_errors = array();
        $title = $this->title;
        if ( empty( $title ) ) {
            $validation_errors[] = new WP_Error(
                'empty_title',
                __( 'title cannot be empty', 'woothemes-sensei' )
            );
        }
        if ( count( $validation_errors ) > 0 ) {
            return $this->validation_error( $validation_errors );
        }
        return true;
    }

    /**
     * @param $error_data array
     * @return WP_Error
     */
    protected function validation_error( $error_data ) {
        return new WP_Error( 'validation-error',  __( 'Validation Error', 'woothemes-sensei' ), $error_data );
    }
}