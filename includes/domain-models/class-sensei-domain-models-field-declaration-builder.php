<?php
// phpcs:ignoreFile
/**
 * Domain Models Field Declaration Builder
 *
 * @package Sensei\Domain Models\Field
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Field_Builder
 * Builds a Sensei_Domain_Models_Field
 *
 * @package Sensei\Domain Models\Field
 * @since 1.9.13
 */
class Sensei_Domain_Models_Field_Declaration_Builder {
	/**
	 * Constructor
	 *
	 * @deprecated 3.11.0
	 *
	 */
	public function __construct() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->args = array(
			'name'              => '',
			'type'              => Sensei_Domain_Models_Field_Declaration::FIELD,
			'required'          => false,
			'map_from'          => null,
			'before_return'     => null,
			'value_type'        => 'any',
			'default_value'     => null,
			'json_name'         => null,
			'supported_outputs' => array( 'json' ),
			'description'       => null,
			'validations'       => array(),
		);
	}

	/**
	 * Gets a field declaration instance.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Field_Declaration Field declaration instance
	 */
	public function build() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new Sensei_Domain_Models_Field_Declaration( $this->args );
	}

	/**
	 * Sets the default value of the field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param mixed $default_value Default value of the field.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_default_value( $default_value ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'default_value', $default_value );
	}

	/**
	 * Sets the field name.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $name Field name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_name( $name ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'name', $name );
	}

	/**
	 * Sets the field type.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $type Field type.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function of_type( $type ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'type', $type );
	}

	/**
	 * Sets the database field name.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $mapped_from Database field name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function map_from( $mapped_from ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'map_from', $mapped_from );
	}

	/**
	 * Sets the function to call when getting the value.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $before_return Function to call when getting the value.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_before_return( $before_return ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'before_return', $before_return );
	}

	/**
	 * Sets whether or not the field is required.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param bool $required true if the field is required, false otherwise.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function required( $required ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'required', $required );
	}

	/**
	 * Sets the supported output types.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array $supported_outputs Supported output types.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_supported_outputs( $supported_outputs = array() ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'supported_outputs', (array) $supported_outputs );
	}

	/**
	 * Sets the field to be invisible.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function not_visible() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->with_supported_outputs( array() );
	}

	/**
	 * Sets the data type of the field.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $value_type Data type of the field.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_value_type( $value_type ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'value_type', $value_type );
	}

	/**
	 * Sets the name to use in JSON output.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $json_name JSON name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_json_name( $json_name ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'json_name', $json_name );
	}

	/**
	 * Sets the field description.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $description Field description.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_description( $description ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'description', $description );
	}

	/**
	 * Sets the validation function names.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $validations Validation function names.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_validations( $validations ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->set( 'validations', is_array( $validations ) ? $validations : array( $validations ) );
	}

	/**
	 * Sets a field property value.
	 *
	 * @param string $name Property name.
	 * @param mixed  $value Property value.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	private function set( $name, $value ) {
		$this->args[ $name ] = $value;
		return $this;
	}
}
