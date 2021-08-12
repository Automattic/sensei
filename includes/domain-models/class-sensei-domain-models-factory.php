<?php
/**
 * Domain Models Factory
 *
 * @package Sensei\Domain Models\Factory
 * @since 1.9.13
 */

/**
 * Domain models factory class.
 *
 * @deprecated 3.11.0
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_Factory {
	/**
	 * Reference to the registry class.
	 *
	 * @var Sensei_Domain_Models_Registry
	 */
	private $registry;
	/**
	 * Name of the registry class.
	 *
	 * @var string
	 */
	private $klass;

	/**
	 * Constructor
	 *
	 * @@deprecated 3.11.0
	 *
	 * @param string $klass Name of the registry class.
	 * @param object $registry Reference to the registry class.
	 */
	public function __construct( $klass, $registry ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->registry = $registry;
		$this->klass    = $klass;
	}

	/**
	 * Creates a domain model from a request.
	 *
	 * @@deprecated 3.11.0
	 *
	 * @param array $request Request.
	 * @return Sensei_Domain_Models_Model_Abstract
	 */
	public function new_from_request( $request ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$fields     = $this->registry->get_field_declarations( $this->klass );
		$field_data = array();
		foreach ( $fields as $field ) {
			if ( isset( $request[ $field->name ] ) ) {
				$field_data[ $field->name ] = $request[ $field->name ];
			} else {
				$field_data[ $field->name ] = $field->get_default_value();
			}
		}

		return $this->create_object( $field_data );
	}

	/**
	 * Creates a domain model for each entity.
	 *
	 * @@deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Model_Collection Domain model collection.
	 */
	public function all() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$results = array();
		foreach ( $this->get_entities() as $entity ) {
			$results[] = $this->create_object( $entity );
		}
		return new Sensei_Domain_Models_Model_Collection( $results );
	}

	/**
	 * Finds an entity and creates a domain model for it.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param int|string $id Entity ID.
	 * @return Sensei_Domain_Models_Model_Abstract|null Domain model object on success, null otherwise.
	 */
	public function find_one_by_id( $id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$entity = $this->get_entity( $id );
		return ! empty( $entity ) ? $this->create_object( $entity ) : null;
	}

	/**
	 * Filters field declarations by type.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $filter_by_type Type to filter on.
	 * @return array Filtered field declarations.
	 */
	public function get_field_declarations( $filter_by_type = null ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->registry->get_field_declarations( $this->klass, $filter_by_type );
	}

	/**
	 * Gets an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param int|string $id Entity ID.
	 * @return mixed Entity.
	 */
	public function get_entity( $id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->get_data_store()->get_entity( $id );
	}

	/**
	 * Gets all entities.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array List of entities.
	 */
	public function get_entities() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->get_data_store()->get_entities();
	}

	/**
	 * Gets an instance of the data store.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Data_Store
	 */
	public function get_data_store() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return Sensei_Domain_Models_Registry::get_instance()
			->get_data_store_for_domain_model( $this->klass );
	}

	/**
	 * Creates a domain model from an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Model_Abstract|array $entity Entity.
	 * @return Sensei_Domain_Models_Model_Abstract Domain model.
	 */
	public function create_object( $entity ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$klass = $this->get_domain_model_class( $this->klass );
		return new $klass( $entity );
	}

	/**
	 * Gets the domain model class name.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $thing Name of the registry class.
	 * @return string Domain model class name.
	 */
	private function get_domain_model_class( $thing ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( ! is_string( $thing ) ) {
			$thing = get_class( $thing );
		}
		return $thing;
	}
}
