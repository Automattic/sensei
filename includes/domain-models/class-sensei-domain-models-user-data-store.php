<?php
/**
 * User Data Store
 *
 * @package Sensei\Domain Models\Data Store\User
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * User data store class.
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_User_Data_Store implements Sensei_Domain_Models_Data_Store {
	/**
	 * Deletes a user.
	 *
	 * @param Sensei_Domain_Models_User $model User model.
	 * @param array                     $args User deletion arguments.
	 */
	public function delete( $model, $args = array() ) {
	}

	/**
	 * Gets all users.
	 *
	 * @return array Empty array.
	 */
	public function get_entities() {
		return array();
	}

	/**
	 * Gets a user.
	 *
	 * @param int $user_id User ID.
	 * @return WP_User|false User object on success, null otherwise.
	 */
	public function get_entity( $user_id ) {
		return get_user_by( 'id', $user_id );
	}

	/**
	 * Gets a meta data field for a user.
	 *
	 * @param Sensei_Domain_Models_User              $user User model.
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration User field declaration.
	 * @return null
	 */
	public function get_meta_field_value( $user, $field_declaration ) {
		return null;
	}

	/**
	 * Inserts or updates a user.
	 *
	 * @param Sensei_Domain_Models_User $entity User.
	 * @param array                     $fields Elements to update or insert.
	 * @param array                     $meta_fields Field values to update or insert.
	 */
	public function upsert( $entity, $fields, $meta_fields = array() ) {
	}
}
