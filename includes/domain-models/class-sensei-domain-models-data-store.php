<?php
// phpcs:ignoreFile
/**
 * Defines an interface that domain model data stores should implement
 *
 * @package Sensei\Domain Models
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @deprecated 3.11.0
 */
interface Sensei_Domain_Models_Data_Store {

	/**
	 * Gets all entities.
	 *
	 * @return Sensei_Domain_Models_Model_Collection
	 */
	public function get_entities();


	/**
	 * Gets an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param int|string $id Entity ID.
	 * @return Sensei_Domain_Models_Model_Abstract
	 */
	public function get_entity( $id );


	/**
	 * Gets a meta data field for an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Model_Abstract    $model Entity.
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Entity fields.
	 * @return mixed
	 */
	public function get_meta_field_value( $model, $field_declaration );

	/**
	 * Deletes an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Model_Abstract $model Entity.
	 * @param array                               $args Entity deletion arguments.
	 * @return mixed
	 */
	public function delete( $model, $args = array() );

	/**
	 * Inserts or updates an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Model_Abstract $entity Entity.
	 * @param array                               $fields Elements to update or insert.
	 * @param array                               $meta_fields Field values to update or insert.
	 * @return mixed
	 */
	public function upsert( $entity, $fields, $meta_fields = array() );


}
