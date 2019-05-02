<?php
/**
 * Module Data Store
 *
 * @package Sensei\Domain Models\Data Store\Module
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Module data store class.
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_Module_Data_Store implements Sensei_Domain_Models_Data_Store {
	/**
	 * Deletes a module.
	 *
	 * @param Sensei_Domain_Models_Module $model Module model.
	 * @param array                       $args Module deletion arguments.
	 */
	public function delete( $model, $args = array() ) {
	}

	/**
	 * Gets all modules.
	 *
	 * @return array List of modules.
	 */
	public function get_entities() {
		$query   = new WP_Term_Query(
			array(
				'taxonomy'   => 'module',
				'hide_empty' => false,
				'fields'     => 'all',
				'order'      => 'ASC',
			)
		);
		$terms   = $query->get_terms();
		$results = array();
		foreach ( $terms as $term ) {
			$results[] = (array) $term;
		}
		return $results;
	}

	/**
	 * Gets a module.
	 *
	 * @param int|string $id Module ID.
	 * @return WP_Term|null Module object on success, null otherwise.
	 */
	public function get_entity( $id ) {
		$term = get_term( absint( $id ), '', ARRAY_A );
		return ! empty( $term ) ? $term : null;
	}

	/**
	 * Gets a meta data field for a module.
	 *
	 * @param Sensei_Domain_Models_Module            $module Module model.
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Module field declaration.
	 * @return null
	 */
	public function get_meta_field_value( $module, $field_declaration ) {
		return null;
	}

	/**
	 * Inserts or updates a module.
	 *
	 * @param Sensei_Domain_Models_Module $entity Module model.
	 * @param array                       $fields Elements to update or insert.
	 * @param array                       $meta_fields Field values to update or insert.
	 */
	public function upsert( $entity, $fields, $meta_fields = array() ) {
	}
}
