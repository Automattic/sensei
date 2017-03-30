<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_Domain_Models_User_Data_Store implements Sensei_Domain_Models_Data_Store {

    public function delete( $model, $args = array() ) {
    }

    public function get_entities() {
        return array();
    }

    public function get_entity( $user_id ) {
        return get_user_by( 'id', $user_id );
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