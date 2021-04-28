<?php
/**
 * Defines an abstract class that domain models should inherit from
 *
 * @package Sensei\Domain Models|Model
 * @since 1.9.13
 */

/**
 * Class Sensei_Domain_Models_Model_Abstract
 *
 * @deprecated 3.11.0
 *
 * @package Sensei\Domain Models|Model
 * @since 1.9.13
 */
abstract class Sensei_Domain_Models_Model_Abstract {

	/**
	 * Data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Raw data.
	 *
	 * @var array|int|null|WP_Comment|WP_Post|WP_User
	 */
	protected $raw_data;

	/**
	 * Fields.
	 *
	 * @var array the model fields Sensei_Domain_Models_Field
	 */
	protected $fields;

	/**
	 * Sensei_Domain_Models_Course constructor.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array|int|WP_Post|WP_Comment|WP_User $data the data object. either an int id, a wp entity.
	 * @since 1.9.13
	 */
	public function __construct( $data = array() ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->fields = self::get_field_declarations( get_class( $this ) );
		$this->data   = array();

		if ( is_array( $data ) ) {
			$model_data = $data;
		} else {
			$model_data = $this->get_data_array_from_entity( $data );
		}

		$this->raw_data = $model_data;

		$post_array_keys = array_keys( $model_data );
		foreach ( $this->fields as $key => $field_declaration ) {
			// eager load anything that is not a meta or derived field.
			if ( false === $field_declaration->is_field() ) {
				continue;
			}
			$this->add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key );
		}
	}

	/**
	 * Gets an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $entity Entity ID.
	 * @return mixed Entity.
	 * @throws Sensei_Domain_Models_Exception If an invalid entity.
	 */
	protected function get_data_array_from_entity( $entity ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( is_numeric( $entity ) ) {
			$data_store = $this->get_data_store();
			return $data_store->get_entity( $entity );
		} elseif ( is_a( $entity, 'WP_Post' ) ) {
			return $entity->to_array();
		} else {
			throw new Sensei_Domain_Models_Exception( 'does not understand entity' );
		}
	}

	/**
	 * Gets the value for a particular field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $field_name Field name.
	 * @return mixed|null Field value.
	 */
	public function __get( $field_name ) {
		return $this->get_value_for( $field_name );
	}

	/**
	 * Gets the value for a particular field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $field_name Field name.
	 * @return mixed Field value.
	 */
	public function get_value_for( $field_name ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( ! isset( $this->fields[ $field_name ] ) ) {
			return null;
		}
		$field_declaration = $this->fields[ $field_name ];
		$this->load_field_value_if_missing( $field_declaration );
		return $this->prepare_value( $field_declaration );
	}

	/**
	 * Sets the value for a particular field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 */
	public function set_value( $field, $value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( ! isset( $this->fields[ $field ] ) ) {
			return;
		}

		$field_declaration                      = $this->fields[ $field ];
		$val                                    = $field_declaration->cast_value( $value );
		$this->data[ $field_declaration->name ] = $val;
	}

	/**
	 * Updates field values sent by a REST API request.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param WP_REST_Request $request REST API request object.
	 * @param bool            $updating true if the fields should be updated, false otherwise.
	 * @return Sensei_Domain_Models_Model_Abstract
	 */
	public function merge_updates_from_request( $request, $updating = false ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$fields     = self::get_field_declarations( get_class( $this ) );
		$field_data = array();
		foreach ( $fields as $field ) {
			if ( $field->is_derived_field() ) {
				continue;
			}
			if ( isset( $request[ $field->name ] ) && ! ( $updating && $field->primary ) ) {
				$field_data[ $field->name ] = $request[ $field->name ];
				$this->set_value( $field->name, $request[ $field->name ] );
			}
		}
		return $this;
	}


	/**
	 * Sets the value for a particular field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Field declaration.
	 * @param array                                  $post_array_keys Model data keys.
	 * @param array                                  $model_data Model data.
	 * @param string                                 $key Field name.
	 */
	protected function add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$map_from = $field_declaration->get_name_to_map_from();
		if ( in_array( $map_from, $post_array_keys ) ) {
			$this->set_value( $key, $model_data[ $map_from ] );
		} elseif ( in_array( $key, $post_array_keys ) ) {
			$this->set_value( $key, $model_data[ $key ] );
		} else {
			$this->set_value( $key, $field_declaration->get_default_value() );
		}
	}

	/**
	 * Sets the value for a particular field if not already set.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Field declaration.
	 */
	protected function load_field_value_if_missing( $field_declaration ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$field_name = $field_declaration->name;
		if ( ! isset( $this->data[ $field_name ] ) ) {
			if ( $field_declaration->is_meta_field() ) {
				$value = $this->get_data_store()->get_meta_field_value( $this, $field_declaration );
				$this->set_value( $field_name, $value );
			} elseif ( $field_declaration->is_derived_field() ) {
				$map_from = $field_declaration->get_name_to_map_from();
				$value    = call_user_func( array( $this, $map_from ) );
				$this->set_value( $field_name, $value );
			} else {
				// load the default value for the field.
				$this->set_value( $field_name, $field_declaration->get_default_value() );
			}
		}
	}

	/**
	 * Inserts or updates an entity.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return int|WP_Error Entity ID on success. Value 0 or WP_Error on failure.
	 */
	public function upsert() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$fields      = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::FIELD );
		$meta_fields = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::META );
		return $this->get_data_store()->upsert( $this, $fields, $meta_fields );
	}

	/**
	 * Deletes an entity.
	 *
	 * @deprecated 3.11.0
	 */
	public function delete() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->get_data_store()->delete( $this );
	}

	/**
	 * Maps JSON name to field name.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array Array of field mappings.
	 */
	public function get_data_transfer_object_field_mappings() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$mappings = array();
		foreach ( self::get_field_declarations( get_class( $this ) ) as $field_declaration ) {
			if ( ! $field_declaration->suppports_output_type( 'json' ) ) {
				continue;
			}
			$mappings[ $field_declaration->json_name ] = $field_declaration->name;
		}
		return $mappings;
	}

	/**
	 * Gets the fields to be inserted or updated.
	 *
	 * @param string $field_type Field type.
	 * @return array Array of field mappings.
	 */
	private function map_field_types_for_upserting( $field_type ) {
		$field_values_to_insert = array();
		foreach ( self::get_field_declarations( get_class( $this ), $field_type ) as $field_declaration ) {
			$what_to_map_to                            = $field_declaration->get_name_to_map_from(); // Field name.
			$field_values_to_insert[ $what_to_map_to ] = $this->get_value_for( $field_declaration->name );
		}
		return $field_values_to_insert;
	}

	/**
	 * Throws an exception if this function has not been overridden.
	 *
	 * @deprecated 3.11.0
	 *
	 * @throws Sensei_Domain_Models_Exception If this function has not been overridden.
	 */
	public static function declare_fields() {
		_deprecated_function( __METHOD__, '3.11.0' );

		throw new Sensei_Domain_Models_Exception( 'override me ' . __FUNCTION__ );
	}

	/**
	 * Throws an exception if this function has not been overridden.
	 *
	 * @deprecated 3.11.0
	 *
	 * @throws Sensei_Domain_Models_Exception If this function has not been overridden.
	 */
	public function get_id() {
		_deprecated_function( __METHOD__, '3.11.0' );

		throw new Sensei_Domain_Models_Exception( 'override me ' . __FUNCTION__ );
	}

	/**
	 * Validates the object instance.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return bool|WP_Error true if instance is valid, WP_Error otherwise.
	 * @throws Sensei_Domain_Models_Exception If validation failed.
	 */
	public function validate() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$validation_errors = array();

		foreach ( $this->fields as $key => $field_declaration ) {
			$is_valid = $this->run_field_validations( $field_declaration );
			if ( is_wp_error( $is_valid ) ) {
				$validation_errors[] = $is_valid->get_error_data();
			}
		}
		if ( count( $validation_errors ) > 0 ) {
			return $this->validation_error( $validation_errors );
		}
		return true;
	}

	/**
	 * Validates the fields.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Field declaration.
	 * @return bool|WP_Error true if field validation passed
	 *                       WP_Error if a required field has no value
	 */
	protected function run_field_validations( $field_declaration ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( $field_declaration->is_derived_field() ) {
			return true;
		}
		$value = $this->get_value_for( $field_declaration->name );
		if ( $field_declaration->required && empty( $value ) ) {
			return new WP_Error(
				'required-field-empty',
				// translators: Placeholder %s is the name of the field.
				sprintf( __( '%s cannot be empty', 'sensei-lms' ), $field_declaration->name )
			);
		} elseif ( ! $field_declaration->required && ! empty( $value ) ) {
			foreach ( $field_declaration->validations as $method_name ) {
				$result = call_user_func( array( $this, $method_name ), $value );
				if ( is_wp_error( $result ) ) {
					$result->add_data(
						array(
							'reason' => $result->get_error_messages(),
							'field'  => $field_declaration->name,
							'value'  => $value,
						)
					);
					return $result;
				}
			}
		}
		return true;
	}

	/**
	 * Gets the field declarations.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array $declared_field_builders Field builders.
	 * @return array Field declarations.
	 */
	public static function initialize_field_map( $declared_field_builders ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$fields = array();
		foreach ( $declared_field_builders as $field_builder ) {
			$field                  = $field_builder->build();
			$fields[ $field->name ] = $field;
		}
		return $fields;
	}

	/**
	 * Filters field declarations by type.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $klass Name of the model class.
	 * @param mixed  $filter_by_type Type to filter on.
	 * @return array Filtered field declarations.
	 */
	public static function get_field_declarations( $klass, $filter_by_type = null ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return Sensei_Domain_Models_Registry::get_instance()
			->get_field_declarations( $klass, $filter_by_type );
	}

	/**
	 * Gets an instance of the field declaration builder.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance.
	 */
	protected static function field() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new Sensei_Domain_Models_Field_Declaration_Builder();
	}

	/**
	 * Sets the type of a meta field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance.
	 */
	protected static function meta_field() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return self::field()->of_type( Sensei_Domain_Models_Field_Declaration::META );
	}

	/**
	 * Sets the type of a derived field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance.
	 */
	protected static function derived_field() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return self::field()->of_type( Sensei_Domain_Models_Field_Declaration::DERIVED );
	}

	/**
	 * Converts a value to a boolean.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $value Value to convert.
	 * @return bool Value converted to a boolean.
	 */
	protected function as_bool( $value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return (bool) $value;
	}

	/**
	 * Converts a value to a non-negative integer.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $value Value to convert.
	 * @return int Value converted to an integer.
	 */
	protected function as_uint( $value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return absint( $value );
	}

	/**
	 * Converts a value to a non-negative integer, if possible.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $value Value to convert.
	 * @return int|null Value converted to an integer, or null if $value is empty or not a number.
	 */
	protected function as_nullable_uint( $value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return ( empty( $value ) && ! is_numeric( $value ) ) ? null : $this->as_uint( $value );
	}

	/**
	 * Prepares fields by converting them to the proper data type.
	 *
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Field declaration.
	 * @return mixed|null
	 */
	private function prepare_value( $field_declaration ) {
		$value = $this->data[ $field_declaration->name ];

		if ( isset( $field_declaration->before_return ) && ! empty( $field_declaration->before_return ) ) {
			return call_user_func_array( array( $this, $field_declaration->before_return ), array( $value ) );
		}

		return $value;
	}

	/**
	 * Gets an instance of the data store.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Data_Store Data Store.
	 * @throws Sensei_Domain_Models_Exception If no data store exists.
	 */
	protected function get_data_store() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return Sensei_Domain_Models_Registry::get_instance()->get_data_store_for_domain_model( get_class( $this ) );
	}
}
