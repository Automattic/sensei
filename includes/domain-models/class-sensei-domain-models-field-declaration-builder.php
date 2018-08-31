<?php
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
	 */
	public function __construct() {
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
	 * @return Sensei_Domain_Models_Field_Declaration Field declaration instance
	 */
	public function build() {
		return new Sensei_Domain_Models_Field_Declaration( $this->args );
	}

	/**
	 * Sets the default value of the field.
	 *
	 * @param mixed $default_value Default value of the field.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_default_value( $default_value ) {
		return $this->set( 'default_value', $default_value );
	}

	/**
	 * Sets the field name.
	 *
	 * @param string $name Field name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_name( $name ) {
		return $this->set( 'name', $name );
	}

	/**
	 * Sets the field type.
	 *
	 * @param string $type Field type.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function of_type( $type ) {
		return $this->set( 'type', $type );
	}

	/**
	 * Sets the database field name.
	 *
	 * @param string $mapped_from Database field name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function map_from( $mapped_from ) {
		return $this->set( 'map_from', $mapped_from );
	}

	/**
	 * Sets the function to call when getting the value.
	 *
	 * @param string $before_return Function to call when getting the value.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_before_return( $before_return ) {
		return $this->set( 'before_return', $before_return );
	}

	/**
	 * Sets whether or not the field is required.
	 *
	 * @param bool $required true if the field is required, false otherwise.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function required( $required ) {
		return $this->set( 'required', $required );
	}

	/**
	 * Sets the supported output types.
	 *
	 * @param array $supported_outputs Supported output types.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_supported_outputs( $supported_outputs = array() ) {
		return $this->set( 'supported_outputs', (array) $supported_outputs );
	}

	/**
	 * Sets the field to be invisible.
	 *
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function not_visible() {
		return $this->with_supported_outputs( array() );
	}

	/**
	 * Sets the data type of the field.
	 *
	 * @param string $value_type Data type of the field.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_value_type( $value_type ) {
		return $this->set( 'value_type', $value_type );
	}

	/**
	 * Sets the name to use in JSON output.
	 *
	 * @param string $json_name JSON name.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_json_name( $json_name ) {
		return $this->set( 'json_name', $json_name );
	}

	/**
	 * Sets the field description.
	 *
	 * @param string $description Field description.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_description( $description ) {
		return $this->set( 'description', $description );
	}

	/**
	 * Sets the validation function names.
	 *
	 * @param string $validations Validation function names.
	 * @return Sensei_Domain_Models_Field_Declaration_Builder Field declaration builder instance
	 */
	public function with_validations( $validations ) {
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
