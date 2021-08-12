<?php
/**
 * Domain Models Field Declaration
 *
 * @package Sensei\Domain Models\Field
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Field_Declaration
 *
 * @deprecated 3.11.0
 *
 * @package Sensei\Domain Models\Field
 */
class Sensei_Domain_Models_Field_Declaration {
	const FIELD   = 'field';
	const META    = 'meta_field';
	const DERIVED = 'derived_field';

	const STRING_VALUE  = 'string';
	const INT_VALUE     = 'integer';
	const ARRAY_VALUE   = 'array';
	const OBJECT_VALUE  = 'object';
	const BOOLEAN_VALUE = 'boolean';
	const ANY_VALUE     = 'any';
	const ENUM          = 'enum';

	/**
	 * Function to call before getting the value.
	 *
	 * @var string
	 */
	public $before_return;
	/**
	 * Function to call before outputting the value.
	 *
	 * @var string
	 */
	public $before_output;
	/**
	 * Database field name.
	 *
	 * @var string
	 */
	public $map_from;
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Field name.
	 *
	 * @var string
	 */
	public $name;
	/**
	 * Whether this field is the primary key field.
	 *
	 * @var bool
	 */
	public $primary;
	/**
	 * Whether this field is required.
	 *
	 * @var bool
	 */
	public $required;
	/**
	 * Supported output types.
	 *
	 * @var array
	 */
	public $supported_outputs;
	/**
	 * Field description.
	 *
	 * @var string
	 */
	public $description;
	/**
	 * Name to use in JSON output.
	 *
	 * @var string
	 */
	public $json_name;
	/**
	 * Validation function names.
	 *
	 * @var array
	 */
	public $validations;
	/**
	 * Default value of the field.
	 *
	 * @var mixed
	 */
	private $default_value;
	/**
	 * Data type of the field.
	 *
	 * @var string
	 */
	private $value_type;
	/**
	 * Accepted field types.
	 *
	 * @var array
	 */
	private $accepted_field_types = array(
		self::FIELD,
		self::META,
		self::DERIVED,
	);

	/**
	 * Constructor
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array $args Field properties.
	 * @throws Sensei_Domain_Models_Exception If the provided argument does not have a 'name' or
	 *                                        'type' property, or if the 'type' is not one of the
	 *                                        accepted types.
	 */
	public function __construct( $args ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( ! isset( $args['name'] ) ) {
			throw new Sensei_Domain_Models_Exception( 'every field should have a name' );
		}
		if ( ! isset( $args['type'] ) || ! in_array( $args['type'], $this->accepted_field_types ) ) {
			throw new Sensei_Domain_Models_Exception( 'every field should have a type (one of ' . implode( ',', $this->accepted_field_types ) . ')' );
		}
		$this->name              = $args['name'];
		$this->type              = $args['type'];
		$this->map_from          = $this->value_or_default( $args, 'map_from' );
		$this->before_return     = $this->value_or_default( $args, 'before_return' );
		$this->primary           = $this->value_or_default( $args, 'primary', false );
		$this->required          = $this->value_or_default( $args, 'required', false );
		$this->supported_outputs = $this->value_or_default( $args, 'supported_outputs', array( 'json' ) );
		$this->json_name         = $this->value_or_default( $args, 'json_name', $this->name );
		$this->value_type        = $this->value_or_default( $args, 'value_type', 'any' );
		$this->default_value     = $this->value_or_default( $args, 'default_value' );
		$this->description       = $this->value_or_default( $args, 'description', '' );
		$this->validations       = $this->value_or_default( $args, 'validations', array() );
	}

	/**
	 * Gets a field property value.
	 *
	 * @param array  $args Field properties.
	 * @param string $name Property name.
	 * @param mixed  $default Default property value.
	 * @return mixed Property value.
	 */
	private function value_or_default( $args, $name, $default = null ) {
		return isset( $args[ $name ] ) ? $args[ $name ] : $default;
	}

	/**
	 * Gets whether or not the field is a meta field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return bool Whether or not the field is a meta field.
	 */
	public function is_meta_field() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return self::META === $this->type;
	}

	/**
	 * Gets whether the field is a derived field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return bool Whether or not the field is a derived field.
	 */
	public function is_derived_field() {
		return self::DERIVED === $this->type;
	}

	/**
	 * Gets whether or not the field is a regular field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return bool Whether or not the field is a regular field.
	 */
	public function is_field() {
		return self::FIELD === $this->type;
	}

	/**
	 * Gets the field name.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return string Field name.
	 */
	public function get_name_to_map_from() {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( isset( $this->map_from ) && ! empty( $this->map_from ) ) {
			return $this->map_from;
		}

		return $this->name;
	}

	/**
	 * Gets the default value.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return mixed Default value.
	 */
	public function get_default_value() {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( isset( $this->default_value ) && ! empty( $this->default_value ) ) {
			return ( is_array( $this->default_value ) && is_callable( $this->default_value ) ) ? call_user_func( $this->default_value ) : $this->default_value;
		}

		if ( self::INT_VALUE === $this->value_type ) {
			return 0;
		}

		if ( self::STRING_VALUE === $this->value_type ) {
			return '';
		}

		if ( self::ARRAY_VALUE === $this->value_type ) {
			return array();
		}

		if ( self::OBJECT_VALUE === $this->value_type ) {
			return null;
		}

		return null;
	}

	/**
	 * Gets the field value.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $value Field value.
	 * @return mixed Field value casted to the proper type.
	 */
	public function cast_value( $value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( self::INT_VALUE === $this->value_type ) {
			return intval( $value, 10 );
		}

		if ( self::STRING_VALUE === $this->value_type ) {
			return '' . $value;
		}

		if ( self::ARRAY_VALUE === $this->value_type ) {
			return (array) $value;
		}

		return $value;
	}

	/**
	 * Checks if the field supports a particular output type.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $type Output type.
	 * @return bool true if the output type is supported, false otherwise.
	 */
	public function suppports_output_type( $type ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return in_array( $type, $this->supported_outputs, true );
	}

	/**
	 * Gets the schema for a field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array Field schema.
	 */
	public function as_item_schema_property() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$schema = array(
			'description' => $this->description,
			'type'        => $this->value_type,
			'required'    => $this->required,
			'context'     => array( 'view', 'edit' ),
		);
		return $schema;
	}
}
