<?php
/**
 * Domain Models Registry
 *
 * @package Sensei\Domain Models\Registry
 * @since 1.9.13
 */

/**
 * Domain Models Registry.
 *
 * Central storage for frequently used objects.
 *
 * @deprecated 3.11.0
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_Registry {
	/**
	 * Registry class instance.
	 *
	 * @var Sensei_Domain_Models_Registry
	 */
	private static $instance = null;
	/**
	 * Data stores for the domain models.
	 *
	 * @var array
	 */
	protected $data_stores;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->field_declarations_by_model = array();
		$this->factories                   = array();
		$this->data_stores                 = array();
	}

	/**
	 * Gets an instance of the registry.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Registry Registry.
	 */
	public static function get_instance() {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gets all domain model factories.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the domain model class.
	 * @return array Domain model factories.
	 */
	public function get_factory( $klass ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$klass = is_string( $klass ) ? $klass : get_class( $klass );
		$this->get_field_declarations( $klass );
		if ( ! isset( $this->factories[ $klass ] ) ) {
			$this->factories[ $klass ] = new Sensei_Domain_Models_Factory( $klass, $this );
		}

		return $this->factories[ $klass ];
	}

	/**
	 * Creates a domain model from a request.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the domain model class.
	 * @param array  $request Request.
	 * @return Sensei_Domain_Models_Model_Abstract Domain model.
	 */
	public function new_from_request( $klass, $request ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$fields     = $this->get_field_declarations( $klass );
		$field_data = array();
		foreach ( $fields as $field ) {
			if ( isset( $request[ $field->name ] ) ) {
				$field_data[ $field->name ] = $request[ $field->name ];
			} else {
				$field_data[ $field->name ] = $field->get_default_value();
			}
		}

		return $this->create_object( $klass, $field_data );
	}

	/**
	 * Creates a domain model for each entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the domain model class.
	 * @return Sensei_Domain_Models_Model_Collection Domain model collection.
	 */
	public function all( $klass ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$results = array();
		foreach ( $this->get_entities( $klass ) as $entity ) {
			$results[] = $this->create_object( $klass, $entity );
		}
		return new Sensei_Domain_Models_Model_Collection( $results );
	}

	/**
	 * Finds an entity and creates a domain model for it.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string     $klass Name of the domain model class.
	 * @param int|string $id Entity ID.
	 * @return Sensei_Domain_Models_Model_Abstract|null Domain model object on success, null otherwise.
	 */
	public function find_one_by_id( $klass, $id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$entity = $this->get_entity( $klass, $id );
		return ! empty( $entity ) ? $this->create_object( $klass, $entity ) : null;
	}

	/**
	 * Gets an entity of a particular domain model.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string     $klass Name of the domain model class.
	 * @param int|string $id Entity ID.
	 * @return mixed Entity.
	 */
	public function get_entity( $klass, $id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->call_fn( $klass, 'get_entity', $id );
	}

	/**
	 * Gets all entities of a particular domain model.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the domain model class.
	 * @return array List of entities.
	 */
	public function get_entities( $klass ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->call_fn( $klass, 'get_entities' );
	}

	/**
	 * Executes the callback function in a domain model class.
	 *
	 * @return mixed
	 */
	private function call_fn() {
		$args    = func_get_args();
		$klass   = array_shift( $args );
		$fn_name = array_shift( $args );
		return call_user_func_array( array( $this->get_domain_model_class( $klass ), $fn_name ), $args );
	}

	/**
	 * Creates a domain model from an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string                                    $klass Name of the domain model class.
	 * @param Sensei_Domain_Models_Model_Abstract|array $entity Entity.
	 * @return Sensei_Domain_Models_Model_Abstract Domain model.
	 */
	public function create_object( $klass, $entity ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$klass = $this->get_domain_model_class( $klass );
		return new $klass( $entity );
	}

	/**
	 * Gets the domain model class name.
	 *
	 * @param string $thing Name of the domain model class.
	 * @return string Domain model class name.
	 * @throws Sensei_Domain_Models_Exception If the domain model has not been registered.
	 */
	private function get_domain_model_class( $thing ) {
		$thing = $this->ensure_class_string( $thing );

		$this->get_field_declarations( $thing );

		if ( ! in_array( $thing, array_keys( $this->field_declarations_by_model ) ) ) {
			throw new Sensei_Domain_Models_Exception( 'Domain Model not registered' );
		}
		return $thing;
	}

	/**
	 * Filters field declarations by type.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the domain model class.
	 * @param mixed  $filter_by_type Type to filter on.
	 * @return array Filtered field declarations.
	 * @throws Sensei_Domain_Models_Exception If class argument does not extend
	 *                                        Sensei_Domain_Models_Model_Abstract.
	 */
	public function get_field_declarations( $klass, $filter_by_type = null ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$super = 'Sensei_Domain_Models_Model_Abstract';
		if ( ! is_subclass_of( $klass, $super ) ) {
			throw new Sensei_Domain_Models_Exception( $klass . ' is not a subclass of ' . $super );
		}

		if ( ! isset( $this->field_declarations_by_model[ $klass ] ) ||
			null === $this->field_declarations_by_model[ $klass ] ) {
			// lazy-load model declarations when the first model if this type is constructed.
			$fields                                      = call_user_func( array( $klass, 'declare_fields' ) );
			$this->field_declarations_by_model[ $klass ] = call_user_func( array( $klass, 'initialize_field_map' ), $fields );
		}
		if ( null === $filter_by_type ) {
			return $this->field_declarations_by_model[ $klass ];
		}
		$filtered = array();
		foreach ( $this->field_declarations_by_model[ $klass ] as $field_declaration ) {
			if ( $field_declaration->type === $filter_by_type ) {
				$filtered[] = $field_declaration;
			}
		}
		return $filtered;
	}

	/**
	 * Sets the data store for a particular domain model class.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string                          $type_class Name of the domain model class.
	 * @param Sensei_Domain_Models_Data_Store $data_store Data store.
	 * @return Sensei_Domain_Models_Registry Domain model registry.
	 */
	public function set_data_store_for_domain_model( $type_class, $data_store ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set_data_store( $type_class, $data_store );
	}

	/**
	 * Gets the data store for a particular domain model class.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $type_class Name of the domain model class.
	 * @return Sensei_Domain_Models_Data_Store|null Data store if it exists, null otherwise.
	 * @throws Sensei_Domain_Models_Exception If no data store exists..
	 */
	public function get_data_store_for_domain_model( $type_class ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$type_class = $this->ensure_class_string( $type_class );
		if ( ! isset( $this->data_stores[ $type_class ] ) ) {
			throw new Sensei_Domain_Models_Exception( 'No datastore set for class ' . $type_class );
		}
		return $this->get_data_store( $type_class );
	}

	/**
	 * Sets the data store for a particular domain model class.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string                          $name Name of the domain model class.
	 * @param Sensei_Domain_Models_Data_Store $data_store_instance Data store.
	 * @return Sensei_Domain_Models_Registry Domain model registry.
	 */
	public function set_data_store( $name, $data_store_instance ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->data_stores[ $name ] = $data_store_instance;
		return $this;
	}

	/**
	 * Gets the data store for a particular domain model class.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $name Name of the domain model class.
	 * @return Sensei_Domain_Models_Data_Store|null Data store if it exists, null otherwise.
	 */
	public function get_data_store( $name ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( isset( $this->data_stores[ $name ] ) ) {
			return $this->data_stores[ $name ];
		}
		return null;
	}

	/**
	 * Gets the class name.
	 *
	 * @param string|object $thing Class name or class instance.
	 * @return string Class name.
	 */
	private function ensure_class_string( $thing ) {
		if ( ! is_string( $thing ) ) {
			return get_class( $thing );
		}
		return $thing;
	}
}
