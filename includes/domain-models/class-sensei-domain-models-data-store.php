<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Interface Sensei_Domain_Models_Data_Store
 */
interface Sensei_Domain_Models_Data_Store {

    /**
     * @return Sensei_Domain_Models_Model_Collection
     */
    public function get_entities();


    /**
     * @param $id the id of the entity
     * @return Sensei_Domain_Models_Model_Abstract
     */
    public function get_entity( $id );


    /**
     * @param $entity Sensei_Domain_Models_Model_Abstract
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @return mixed
     */
    public function get_meta_field_value( $model, $field_declaration );

    /**
     * @param $model Sensei_Domain_Models_Model_Abstract
     * @param array $args
     * @return mixed
     */
    public function delete( $model, $args = array() );

    /**
     * @param $entity Sensei_Domain_Models_Model_Abstract
     * @return mixed
     */
    public function upsert( $entity, $fields, $meta_fields = array() );


}