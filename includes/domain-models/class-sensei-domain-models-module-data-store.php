<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_Domain_Models_Module_Data_Store implements Sensei_Domain_Models_Data_Store {

    public function delete( $model, $args = array() ) {
    }

    public function get_entities() {
        $query = new WP_Term_Query( array(
            'taxonomy'   => 'module',
            'hide_empty' => false,
            'fields'     => 'all',
            'order'      => 'ASC',
        ) );
        $terms = $query->get_terms();
        $results = array();
        foreach ($terms as $term) {
            $results[] = (array)$term;
        }
        return $results;
    }

    public function get_entity( $id ) {
        $term = get_term( absint( $id ), '', ARRAY_A );
        return !empty( $term ) ? $term : null;
    }

    /**
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @return mixed
     */
    public function get_meta_field_value( $course, $field_declaration ) {
        return null;
    }

    /**
     * @param $entity Sensei_Domain_Models_Model_Abstract
     * @return mixed
     */
    public function upsert($entity, $fields, $meta_fields = array()) {
    }
}